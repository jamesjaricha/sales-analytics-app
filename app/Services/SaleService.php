<?php

namespace App\Services;

use App\Enums\PaymentMethod;
use App\Models\DailySalesReport;
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

            $sale = Sale::create([
                'reference' => $this->generateReference($businessDate),
                'user_id' => $user->id,
                'business_date' => $businessDate,
                'payment_method' => $method->value,
                'total_amount' => $total,
                'amount_due' => $method->isCredit() ? $total : 0,
                'customer_name' => $data['customer_name'] ?? null,
                'note' => $data['note'] ?? null,
                'status' => 'completed',
            ]);

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
