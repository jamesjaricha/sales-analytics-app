<?php

namespace App\Http\Controllers;

use App\Models\DailySalesReport;
use App\Services\DayEndService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DayEndController extends Controller
{
    public function __construct(private readonly DayEndService $dayEnd) {}

    /**
     * The day-end reconciliation wizard for today.
     */
    public function create()
    {
        $today = now()->toDateString();

        if ($existing = $this->dayEnd->alreadyApproved($today)) {
            return redirect()->route('day-end.show', $existing)
                ->with('info', 'The day-end for today has already been approved.');
        }

        return view('day-end.create', [
            'summary' => $this->dayEnd->summary($today),
        ]);
    }

    /**
     * Approve & lock today's day-end.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'expenses' => ['nullable', 'array'],
            'expenses.*.description' => ['nullable', 'required_with:expenses.*.amount', 'string', 'max:255'],
            'expenses.*.amount' => ['nullable', 'numeric', 'min:0'],
            'expenses.*.payment_method' => ['nullable', 'in:cash,bank,mobile_money'],
            'counted_cash' => ['nullable', 'numeric', 'min:0'],
        ]);

        $report = $this->dayEnd->approve(
            now()->toDateString(),
            Auth::user(),
            $validated['expenses'] ?? [],
            isset($validated['counted_cash']) ? (float) $validated['counted_cash'] : null,
        );

        return redirect()->route('day-end.show', $report)
            ->with('success', "Day-end approved and locked — {$report->sales()->count()} invoices reconciled.");
    }

    /**
     * Reopen today's approved day-end (admin only) so inputs can be corrected
     * and the day re-approved with recomputed figures.
     */
    public function reopen(DailySalesReport $dayEnd)
    {
        $this->dayEnd->reopen($dayEnd, Auth::user());

        return redirect()->route('day-end.create')->with(
            'success',
            'Day-end reopened — correct the inputs (balance b/f, invoices, expenses) and approve again.',
        );
    }

    /**
     * View an approved day-end report.
     */
    public function show(DailySalesReport $dayEnd)
    {
        $dayEnd->load(['deductions', 'approvedBy', 'debtPayments.sale', 'debtPayments.receivedBy', 'sales' => fn ($q) => $q->latest(), 'sales.salePayments']);

        return view('day-end.show', ['report' => $dayEnd]);
    }

    /**
     * Download an approved day-end as a PDF.
     */
    public function pdf(DailySalesReport $dayEnd)
    {
        $dayEnd->load(['deductions', 'approvedBy', 'debtPayments.sale', 'debtPayments.receivedBy', 'sales' => fn ($q) => $q->latest(), 'sales.salePayments']);

        return Pdf::loadView('day-end.pdf', ['report' => $dayEnd])
            ->download('day-end-'.$dayEnd->sale_date->format('Y-m-d').'.pdf');
    }
}
