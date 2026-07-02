<?php

namespace App\Services;

use App\Enums\PaymentMethod;
use App\Models\DailySalesReport;
use App\Models\Sale;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class DayEndService
{
    /**
     * Settlement summary for a trading day, computed from the day's completed,
     * not-yet-reconciled invoices.
     *
     * @return array{
     *     business_date: string, invoice_count: int, gross_sales: float,
     *     total_cash: float, total_bank: float, total_mobile_money: float,
     *     total_outstanding: float, sales: \Illuminate\Support\Collection
     * }
     */
    public function summary(string $businessDate): array
    {
        $sales = Sale::with('items')
            ->completed()
            ->unreconciled()
            ->forDate($businessDate)
            ->latest()
            ->get();

        // Full invoices in a method, plus partial payments on credit invoices
        // settled via that method.
        $byMethod = fn (PaymentMethod $m): float => (float) $sales
            ->filter(fn (Sale $s) => $s->payment_method === $m)
            ->sum(fn (Sale $s) => (float) $s->total_amount)
            + (float) $sales
                ->filter(fn (Sale $s) => $s->payment_method === PaymentMethod::Credit && $s->paid_via === $m->value)
                ->sum(fn (Sale $s) => (float) $s->paid_amount);

        return [
            'business_date' => $businessDate,
            'invoice_count' => $sales->count(),
            'gross_sales' => (float) $sales->sum(fn (Sale $s) => (float) $s->total_amount),
            'total_cash' => $byMethod(PaymentMethod::Cash),
            'total_bank' => $byMethod(PaymentMethod::Bank),
            'total_mobile_money' => $byMethod(PaymentMethod::MobileMoney),
            'total_outstanding' => (float) $sales
                ->filter(fn (Sale $s) => $s->payment_method === PaymentMethod::Credit)
                ->sum(fn (Sale $s) => (float) $s->amount_due),
            'sales' => $sales,
        ];
    }

    /**
     * The approved day-end for a date, if one exists.
     */
    public function alreadyApproved(string $businessDate): ?DailySalesReport
    {
        return DailySalesReport::whereDate('sale_date', $businessDate)
            ->whereNotNull('approved_at')
            ->first();
    }

    /**
     * Approve and lock the day-end: persist the reconciliation, record expenses
     * (cash, bank or mobile money), and attach the day's invoices to the report.
     *
     * Drawer maths: cash at hand = balance b/f + cash takings − cash expenses.
     * Bank/mobile expenses never touch the drawer.
     *
     * @param  array<int, array{description?: string|null, amount?: float|int|string|null, payment_method?: string|null}>  $expenses
     */
    public function approve(
        string $businessDate,
        User $admin,
        array $expenses = [],
        ?float $countedCash = null,
        ?float $openingBalance = null,
    ): DailySalesReport {
        if ($this->alreadyApproved($businessDate)) {
            throw ValidationException::withMessages([
                'business_date' => "The day-end for {$businessDate} has already been approved.",
            ]);
        }

        $summary = $this->summary($businessDate);

        if ($summary['invoice_count'] === 0) {
            throw ValidationException::withMessages([
                'business_date' => "There are no sales to reconcile for {$businessDate}.",
            ]);
        }

        return DB::transaction(function () use ($summary, $admin, $expenses, $countedCash, $openingBalance, $businessDate) {
            $totalDeductions = 0.0;
            $cashExpenses = 0.0;
            foreach ($expenses as $expense) {
                $amount = (float) ($expense['amount'] ?? 0);
                $totalDeductions += $amount;
                if (($expense['payment_method'] ?? PaymentMethod::Cash->value) === PaymentMethod::Cash->value) {
                    $cashExpenses += $amount;
                }
            }

            // Only cash expenses leave the drawer; bank/mobile expenses come out
            // of their own settlement lines.
            $cashAtHand = ($openingBalance ?? 0.0) + $summary['total_cash'] - $cashExpenses;

            $report = DailySalesReport::create([
                'user_id' => $admin->id,
                'sale_date' => $businessDate,
                'total_sales_value' => $summary['gross_sales'],
                'total_deductions' => $totalDeductions,
                'cash_at_hand' => $cashAtHand,
                'status' => 'completed',
                'total_cash' => $summary['total_cash'],
                'total_bank' => $summary['total_bank'],
                'total_mobile_money' => $summary['total_mobile_money'],
                'total_outstanding' => $summary['total_outstanding'],
                'counted_cash' => $countedCash,
                'opening_balance' => $openingBalance,
                'approved_by' => $admin->id,
                'approved_at' => now(),
            ]);

            foreach ($expenses as $expense) {
                if (! empty($expense['description']) && ! empty($expense['amount'])) {
                    $report->deductions()->create([
                        'description' => $expense['description'],
                        'amount' => (float) $expense['amount'],
                        'payment_method' => $expense['payment_method'] ?? PaymentMethod::Cash->value,
                    ]);
                }
            }

            // Lock the day's invoices into this report
            Sale::completed()->unreconciled()->forDate($businessDate)
                ->update(['day_end_report_id' => $report->id]);

            return $report;
        });
    }
}
