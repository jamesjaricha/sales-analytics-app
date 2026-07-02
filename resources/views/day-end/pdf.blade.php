<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        * { font-family: DejaVu Sans, sans-serif; }
        body { font-size: 12px; color: #1f2937; margin: 0; }
        h1 { font-size: 20px; margin: 0 0 2px; }
        .muted { color: #6b7280; font-size: 11px; }
        .row { width: 100%; border-collapse: collapse; }
        .breakdown td { padding: 6px 8px; border: 1px solid #e5e7eb; }
        .breakdown .label { color: #6b7280; font-size: 10px; }
        .breakdown .val { font-size: 14px; font-weight: bold; }
        .cash-box { background: #ecfdf5; border: 1px solid #a7f3d0; padding: 12px; margin: 16px 0; text-align: center; }
        .cash-box .amt { font-size: 22px; font-weight: bold; color: #065f46; }
        table.list { width: 100%; border-collapse: collapse; margin-top: 6px; }
        table.list th { text-align: left; border-bottom: 2px solid #e5e7eb; padding: 5px 6px; font-size: 10px; color: #6b7280; text-transform: uppercase; }
        table.list td { padding: 5px 6px; border-bottom: 1px solid #f3f4f6; }
        .right { text-align: right; }
        .section-title { font-size: 13px; font-weight: bold; margin: 18px 0 4px; }
    </style>
</head>
<body>
    <h1>Day-End Report</h1>
    <p class="muted">
        {{ \Carbon\Carbon::parse($report->sale_date)->format('l, d F Y') }}
        @if($report->isApproved()) &middot; Approved{{ $report->approvedBy ? ' by '.$report->approvedBy->name : '' }} {{ $report->approved_at->format('d M Y H:i') }} @endif
    </p>

    <table class="row breakdown" style="margin-top: 12px;">
        <tr>
            <td><div class="label">Gross sales</div><div class="val">ZMW {{ number_format($report->total_sales_value, 2) }}</div></td>
            <td><div class="label">Cash</div><div class="val">ZMW {{ number_format($report->total_cash, 2) }}</div></td>
            <td><div class="label">Cash @ Bank</div><div class="val">ZMW {{ number_format($report->total_bank, 2) }}</div></td>
        </tr>
        <tr>
            <td><div class="label">Mobile money</div><div class="val">ZMW {{ number_format($report->total_mobile_money, 2) }}</div></td>
            <td><div class="label">Outstanding debt</div><div class="val">ZMW {{ number_format($report->total_outstanding, 2) }}</div></td>
            <td><div class="label">Total expenses</div><div class="val">ZMW {{ number_format($report->total_deductions, 2) }}</div></td>
        </tr>
    </table>

    @php($pdfCashExpenses = $report->deductions->where('payment_method', 'cash')->sum('amount'))
    <div class="cash-box">
        <div class="muted">Cash at hand (b/f {{ number_format((float) ($report->opening_balance ?? 0), 2) }} + cash {{ number_format($report->total_cash, 2) }} &minus; cash expenses {{ number_format((float) $pdfCashExpenses, 2) }})</div>
        <div class="amt">ZMW {{ number_format($report->cash_at_hand, 2) }}</div>
        @if($report->counted_cash !== null)
            @php($variance = (float) $report->counted_cash - (float) $report->cash_at_hand)
            <div class="muted">Counted {{ number_format($report->counted_cash, 2) }} &middot; Variance {{ $variance > 0 ? '+' : '' }}{{ number_format($variance, 2) }}</div>
        @endif
    </div>

    @if($report->deductions->count())
        <div class="section-title">Expenses</div>
        <table class="list">
            @foreach($report->deductions as $deduction)
                <tr>
                    <td>{{ $deduction->description }}</td>
                    <td>{{ ['cash' => 'Cash', 'bank' => 'Bank', 'mobile_money' => 'Mobile Money'][$deduction->payment_method ?? 'cash'] ?? $deduction->payment_method }}</td>
                    <td class="right">ZMW {{ number_format($deduction->amount, 2) }}</td>
                </tr>
            @endforeach
        </table>
    @endif

    <div class="section-title">Reconciled invoices ({{ $report->sales->count() }})</div>
    <table class="list">
        <thead>
            <tr><th>Reference</th><th>Time</th><th>Method</th><th class="right">Amount</th></tr>
        </thead>
        <tbody>
            @forelse($report->sales as $invoice)
                <tr>
                    <td>{{ $invoice->reference }}</td>
                    <td>{{ $invoice->created_at->format('H:i') }}</td>
                    <td>{{ $invoice->payment_method->label() }}@if($invoice->customer_name) ({{ $invoice->customer_name }})@endif @if((float) $invoice->paid_amount > 0) — paid {{ number_format((float) $invoice->paid_amount, 2) }}, owing {{ number_format((float) $invoice->amount_due, 2) }}@endif</td>
                    <td class="right">ZMW {{ number_format($invoice->total_amount, 2) }}</td>
                </tr>
            @empty
                <tr><td colspan="4" class="muted">No invoices.</td></tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>
