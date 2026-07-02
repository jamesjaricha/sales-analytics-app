<?php

namespace App\Services;

use App\Enums\PaymentMethod;
use App\Models\DailySalesReport;
use App\Models\DayOpening;
use App\Models\DebtPayment;
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

        // Debt repayments received today (against any older invoice) settle
        // into today's takings.
        $debtPayments = DebtPayment::with(['sale', 'receivedBy'])
            ->unreconciled()
            ->forDate($businessDate)
            ->latest()
            ->get();

        // Money collected today in a method: full invoices + partial payments
        // on today's credit invoices + debt repayments received today.
        $byMethod = fn (PaymentMethod $m): float => (float) $sales
            ->filter(fn (Sale $s) => $s->payment_method === $m)
            ->sum(fn (Sale $s) => (float) $s->total_amount)
            + (float) $sales
                ->filter(fn (Sale $s) => $s->payment_method === PaymentMethod::Credit && $s->paid_via === $m->value)
                ->sum(fn (Sale $s) => (float) $s->paid_amount)
            + (float) $debtPayments
                ->where('payment_method', $m->value)
                ->sum(fn (DebtPayment $p) => (float) $p->amount);

        return [
            'business_date' => $businessDate,
            'opening_balance' => (float) (DayOpening::forDate($businessDate)?->opening_balance ?? 0),
            'invoice_count' => $sales->count(),
            'gross_sales' => (float) $sales->sum(fn (Sale $s) => (float) $s->total_amount),
            'total_cash' => $byMethod(PaymentMethod::Cash),
            'total_bank' => $byMethod(PaymentMethod::Bank),
            'total_mobile_money' => $byMethod(PaymentMethod::MobileMoney),
            'total_outstanding' => (float) $sales
                ->filter(fn (Sale $s) => $s->payment_method === PaymentMethod::Credit)
                ->sum(fn (Sale $s) => (float) $s->amount_due),
            'sales' => $sales,
            'debt_payments' => $debtPayments,
            'debt_payments_total' => (float) $debtPayments->sum(fn (DebtPayment $p) => (float) $p->amount),
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
     * Money maths: cash at hand = cash takings − cash expenses (the balance
     * b/f is reported as its own summary line, NOT inside cash at hand).
     * The physical drawer count is checked against b/f + cash at hand.
     * Bank/mobile expenses never touch the drawer.
     *
     * @param  array<int, array{description?: string|null, amount?: float|int|string|null, payment_method?: string|null}>  $expenses
     */
    public function approve(
        string $businessDate,
        User $admin,
        array $expenses = [],
        ?float $countedCash = null,
    ): DailySalesReport {
        if ($this->alreadyApproved($businessDate)) {
            throw ValidationException::withMessages([
                'business_date' => "The day-end for {$businessDate} has already been approved.",
            ]);
        }

        $summary = $this->summary($businessDate);

        if ($summary['invoice_count'] === 0 && $summary['debt_payments']->isEmpty()) {
            throw ValidationException::withMessages([
                'business_date' => "There are no sales to reconcile for {$businessDate}.",
            ]);
        }

        return DB::transaction(function () use ($summary, $admin, $expenses, $countedCash, $businessDate) {
            $openingBalance = $summary['opening_balance'];
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
            // of their own settlement lines. Balance b/f is NOT included here —
            // it is stored separately and only affects the drawer-count check.
            $cashAtHand = $summary['total_cash'] - $cashExpenses;

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

            // Lock the day's invoices and debt repayments into this report
            Sale::completed()->unreconciled()->forDate($businessDate)
                ->update(['day_end_report_id' => $report->id]);

            DebtPayment::unreconciled()->forDate($businessDate)
                ->update(['day_end_report_id' => $report->id]);

            return $report;
        });
    }

    /**
     * Reopen TODAY's approved day-end so its inputs can be corrected and the
     * day re-approved. Unlocks the invoices and repayments and removes the
     * report — every figure is recomputed from the corrected inputs on
     * re-approval, so the report can never disagree with its own contents.
     */
    public function reopen(DailySalesReport $report, User $admin): void
    {
        if (! $report->isApproved()) {
            throw ValidationException::withMessages([
                'report' => 'Only an approved day-end can be reopened.',
            ]);
        }

        if (! $report->sale_date->isToday()) {
            throw ValidationException::withMessages([
                'report' => 'Only today\'s day-end can be reopened — older days are permanently locked.',
            ]);
        }

        DB::transaction(function () use ($report) {
            Sale::where('day_end_report_id', $report->id)
                ->update(['day_end_report_id' => null]);

            DebtPayment::where('day_end_report_id', $report->id)
                ->update(['day_end_report_id' => null]);

            $report->deductions()->delete();
            $report->delete();
        });

        logger()->info('Day-end reopened', [
            'report_id' => $report->id,
            'sale_date' => $report->sale_date->toDateString(),
            'reopened_by' => $admin->id,
        ]);
    }
}
