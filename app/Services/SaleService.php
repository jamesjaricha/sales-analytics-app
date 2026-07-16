<?php

namespace App\Services;

use App\Enums\PaymentMethod;
use App\Models\DailySalesReport;
use App\Models\DebtPayment;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SaleService
{
    public function __construct(private readonly StockService $stock) {}

    /**
     * Record a POS invoice: persist the sale + items and deduct stock, atomically.
     *
     * @param  array{
     *     business_date?: string|null,
     *     payment_method: string,
     *     customer_name?: string|null,
     *     paid_amount?: int|float|string|null,
     *     paid_via?: string|null,
     *     tenders?: array<int, array{method?: string|null, amount?: int|float|string|null}>|null,
     *     note?: string|null,
     *     items: array<int, array{product_id?: int|null, product_name: string, quantity: int|string, unit_price: int|float|string}>
     * }  $data
     */
    public function record(array $data, User $user): Sale
    {
        $businessDate = $data['business_date'] ?? now()->toDateString();
        $method = PaymentMethod::from($data['payment_method']);

        // Guard rails before we touch anything
        $this->assertDayOpen($businessDate);
        $this->assertStockAvailable($data['items']);

        return DB::transaction(function () use ($data, $user, $businessDate, $method) {
            $total = 0.0;
            foreach ($data['items'] as $item) {
                $total += (int) $item['quantity'] * (float) $item['unit_price'];
            }

            // Partial payment applies to credit sales only: the paid portion is
            // settled now (via cash/bank/mobile) and the remainder stays outstanding.
            $paidAmount = $method->isCredit() ? (float) ($data['paid_amount'] ?? 0) : 0.0;
            $paidVia = $paidAmount > 0 ? ($data['paid_via'] ?? PaymentMethod::Cash->value) : null;

            if ($paidAmount < 0 || ($method->isCredit() && $paidAmount >= $total)) {
                throw ValidationException::withMessages([
                    'paid_amount' => 'The amount paid now must be less than the invoice total (use a non-credit method for full payment).',
                ]);
            }

            // Split payment: the total is settled through several tender lines;
            // anything not covered stays outstanding (debtor rules apply).
            $tenders = $method === PaymentMethod::Split
                ? $this->validateTenders($data['tenders'] ?? [], $total)
                : [];
            $tendered = round(array_sum(array_column($tenders, 'amount')), 2);
            $splitDue = $method === PaymentMethod::Split ? round($total - $tendered, 2) : 0.0;

            if ($splitDue > 0 && (empty($data['customer_name']) || empty($data['customer_phone']))) {
                throw ValidationException::withMessages([
                    'customer_name' => 'A customer name and phone number are required when a split payment leaves a balance owing.',
                ]);
            }

            $amountDue = match (true) {
                $method->isCredit() => $total - $paidAmount,
                $method === PaymentMethod::Split => $splitDue,
                default => 0,
            };

            $sale = Sale::create([
                'reference' => $this->generateReference($businessDate),
                'user_id' => $user->id,
                'business_date' => $businessDate,
                'payment_method' => $method->value,
                'total_amount' => $total,
                'amount_due' => $amountDue,
                'paid_amount' => $method === PaymentMethod::Split ? $tendered : $paidAmount,
                'paid_via' => $paidVia,
                'customer_name' => $data['customer_name'] ?? null,
                'customer_phone' => $data['customer_phone'] ?? null,
                'note' => $data['note'] ?? null,
                'status' => 'completed',
            ]);

            foreach ($tenders as $tender) {
                $sale->salePayments()->create($tender);
            }

            foreach ($data['items'] as $item) {
                $qty = (int) $item['quantity'];
                $price = (float) $item['unit_price'];

                $saleItem = $sale->items()->create([
                    'product_id' => $item['product_id'] ?? null,
                    'product_name' => $item['product_name'],
                    'quantity' => $qty,
                    'unit_price' => $price,
                    'total_price' => $qty * $price,
                ]);

                $this->deductStock($saleItem, $user->id, $sale->reference);
            }

            return $sale;
        });
    }

    /**
     * Record a repayment against an outstanding credit invoice. The money is
     * settled into the day it is RECEIVED, so it shows up in today's takings
     * and day-end — even if the original invoice's day is already locked.
     */
    public function receivePayment(Sale $sale, float $amount, string $method, User $receiver, ?string $note = null): DebtPayment
    {
        $today = now()->toDateString();

        // A locked day cannot take more money — receive it tomorrow
        $this->assertDayOpen($today);

        $carriesDebt = in_array($sale->payment_method, [PaymentMethod::Credit, PaymentMethod::Split], true);

        if (! $carriesDebt || $sale->status !== 'completed') {
            throw ValidationException::withMessages([
                'sale' => 'Repayments can only be recorded against completed credit or split invoices.',
            ]);
        }

        return DB::transaction(function () use ($sale, $amount, $method, $receiver, $note, $today) {
            // Re-read the balance inside the transaction to block overpayment races
            $sale = Sale::lockForUpdate()->findOrFail($sale->id);
            $due = (float) $sale->amount_due;

            if ($amount <= 0 || $amount > $due + 0.005) {
                throw ValidationException::withMessages([
                    'amount' => 'The payment must be between 0.01 and the outstanding balance of '.number_format($due, 2).'.',
                ]);
            }

            $payment = $sale->payments()->create([
                'amount' => $amount,
                'payment_method' => $method,
                'business_date' => $today,
                'received_by' => $receiver->id,
                'note' => $note,
            ]);

            $sale->update(['amount_due' => max($due - $amount, 0)]);

            return $payment;
        });
    }

    /**
     * Void an invoice (only while it is still unreconciled) and restore its stock.
     */
    public function void(Sale $sale): Sale
    {
        if ($sale->isLocked()) {
            throw ValidationException::withMessages([
                'sale' => 'This invoice is part of an approved day-end and cannot be voided.',
            ]);
        }

        if ($sale->status === 'void') {
            return $sale; // already voided — idempotent
        }

        return DB::transaction(function () use ($sale) {
            $sale->loadMissing('items');

            foreach ($sale->items as $saleItem) {
                $this->restoreStock($saleItem, $sale->user_id, $sale->reference);
            }

            $sale->update(['status' => 'void']);

            return $sale;
        });
    }

    private function deductStock(SaleItem $saleItem, int $userId, string $reference): void
    {
        $product = $saleItem->product_id ? Product::find($saleItem->product_id) : null;

        if (! $product || ! $product->track_stock) {
            return;
        }

        $this->stock->adjustStock(
            product: $product,
            quantity: -$saleItem->quantity,
            type: 'sale',
            userId: $userId,
            notes: 'Sale '.$reference,
            referenceId: $saleItem->id,
            referenceType: SaleItem::class,
            unitCost: $product->cost !== null ? (float) $product->cost : null,
        );
    }

    private function restoreStock(SaleItem $saleItem, int $userId, string $reference): void
    {
        $product = $saleItem->product_id ? Product::find($saleItem->product_id) : null;

        if (! $product || ! $product->track_stock) {
            return;
        }

        $this->stock->adjustStock(
            product: $product,
            quantity: $saleItem->quantity, // add back
            type: 'return',
            userId: $userId,
            notes: 'Void '.$reference,
            referenceId: $saleItem->id,
            referenceType: SaleItem::class,
            unitCost: $product->cost !== null ? (float) $product->cost : null,
        );
    }

    /**
     * Validate and normalise split-payment tender lines.
     *
     * @param  array<int, array{method?: string|null, amount?: int|float|string|null}>  $tenders
     * @return array<int, array{method: string, amount: float}>
     */
    private function validateTenders(array $tenders, float $total): array
    {
        $validMethods = array_map(fn (PaymentMethod $m) => $m->value, PaymentMethod::tenderMethods());
        $clean = [];

        foreach ($tenders as $tender) {
            $method = $tender['method'] ?? null;
            $amount = round((float) ($tender['amount'] ?? 0), 2);

            if (! in_array($method, $validMethods, true)) {
                throw ValidationException::withMessages([
                    'tenders' => 'Each split line must be paid via cash, bank or mobile money.',
                ]);
            }

            if ($amount <= 0) {
                throw ValidationException::withMessages([
                    'tenders' => 'Each split line needs an amount greater than zero.',
                ]);
            }

            $clean[] = ['method' => $method, 'amount' => $amount];
        }

        if ($clean === []) {
            throw ValidationException::withMessages([
                'tenders' => 'Add at least one payment line for a split payment.',
            ]);
        }

        $tendered = round(array_sum(array_column($clean, 'amount')), 2);

        if ($tendered > round($total, 2)) {
            throw ValidationException::withMessages([
                'tenders' => 'The split lines add up to more than the invoice total of '.number_format($total, 2).'.',
            ]);
        }

        return $clean;
    }

    /**
     * Block recording into a day whose day-end has already been approved.
     */
    private function assertDayOpen(string $businessDate): void
    {
        $approved = DailySalesReport::whereDate('sale_date', $businessDate)
            ->whereNotNull('approved_at')
            ->exists();

        if ($approved) {
            throw ValidationException::withMessages([
                'business_date' => "The day-end for {$businessDate} has already been approved. New sales cannot be recorded for this date.",
            ]);
        }
    }

    /**
     * Block selling a tracked product below zero stock.
     *
     * @param  array<int, array{product_id?: int|null, product_name?: string, quantity?: int|string}>  $items
     */
    private function assertStockAvailable(array $items): void
    {
        $requested = [];
        foreach ($items as $item) {
            if (! empty($item['product_id'])) {
                $id = (int) $item['product_id'];
                $requested[$id] = ($requested[$id] ?? 0) + (int) ($item['quantity'] ?? 0);
            }
        }

        if ($requested === []) {
            return;
        }

        $products = Product::whereIn('id', array_keys($requested))->get();

        foreach ($products as $product) {
            if ($product->track_stock && $requested[$product->id] > $product->stock_quantity) {
                throw ValidationException::withMessages([
                    'items' => "Not enough stock for {$product->name} (have {$product->stock_quantity}, need {$requested[$product->id]}).",
                ]);
            }
        }
    }

    /**
     * Per-day sequential reference: INV-YYYYMMDD-####.
     */
    private function generateReference(string $businessDate): string
    {
        $datePart = Carbon::parse($businessDate)->format('Ymd');
        $sequence = Sale::whereDate('business_date', $businessDate)->count() + 1;

        return sprintf('INV-%s-%04d', $datePart, $sequence);
    }
}
