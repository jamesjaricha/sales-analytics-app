<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <title>Sales Report - {{ $report->sale_date->format('Y-m-d') }}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 12px;
            line-height: 1.5;
            color: #333;
        }

        .header {
            text-align: center;
            margin-bottom: 30px;
            border-bottom: 2px solid #333;
            padding-bottom: 10px;
        }

        .header h1 {
            margin: 0;
            font-size: 24px;
            color: #000;
        }

        .header p {
            margin: 5px 0;
            color: #666;
        }

        .info-section {
            margin-bottom: 20px;
        }

        .info-section table {
            width: 100%;
        }

        .info-section td {
            padding: 5px 0;
        }

        .info-label {
            font-weight: bold;
            width: 150px;
        }

        table.items {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }

        table.items th {
            background-color: #f3f4f6;
            border: 1px solid #d1d5db;
            padding: 8px;
            text-align: left;
            font-weight: bold;
        }

        table.items td {
            border: 1px solid #d1d5db;
            padding: 8px;
        }

        table.items .text-right {
            text-align: right;
        }

        table.items .text-center {
            text-align: center;
        }

        .totals-row {
            background-color: #f9fafb;
            font-weight: bold;
        }

        .summary {
            margin-top: 30px;
            padding: 15px;
            background-color: #f0fdf4;
            border: 2px solid #22c55e;
            border-radius: 8px;
        }

        .summary table {
            width: 100%;
        }

        .summary td {
            padding: 5px 0;
        }

        .summary-label {
            font-weight: bold;
        }

        .cash-at-hand {
            font-size: 18px;
            color: #16a34a;
            margin-top: 10px;
            padding-top: 10px;
            border-top: 2px solid #22c55e;
        }

        .footer {
            margin-top: 40px;
            padding-top: 10px;
            border-top: 1px solid #ccc;
            font-size: 10px;
            color: #666;
            text-align: center;
        }

        h2 {
            font-size: 16px;
            margin-top: 20px;
            margin-bottom: 10px;
            color: #000;
        }

        table.cards {
            width: 100%;
            border-collapse: separate;
            border-spacing: 8px;
            margin-top: 8px;
        }

        table.cards td.card {
            border-radius: 8px;
            padding: 12px 14px;
            vertical-align: top;
        }

        .card-label {
            font-size: 11px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 4px;
        }

        .card-value {
            font-size: 20px;
            font-weight: bold;
        }

        table.cash-banner {
            width: 100%;
            margin-top: 8px;
            background-color: #16a34a;
            border-radius: 8px;
        }

        table.cash-banner td {
            padding: 14px 16px;
            color: #ffffff;
        }
    </style>
</head>

<body>

    <!-- Header with Logo and Company Info -->
    <div class="header">
        <table style="width: 100%; margin-bottom: 20px;">
            <tr>
                <td style="width: 20%; text-align: left; vertical-align: middle;">
                    <!-- Logo (uncomment and update path when you add your logo) -->
                    <img src="{{ public_path('images/logo.png') }}" alt="Company Logo" style="max-width: 80px; height: auto;">
                </td>
                <td style="width: 80%; text-align: center; vertical-align: middle;">
                    <p style="margin: 5px 0; font-size: 11px; color: #dc2626 !important;">
                        2 EW Tarry Building Cairo Road, Lusaka Zambia<br>
                        Phone: +260 777 862 690 | Email: info.zambia@ulwazienergy.co.za
                    </p>
                </td>
            </tr>
        </table>
        <div style="text-align: center; border-top: 2px solid green; padding-top: 10px; margin-top: 10px;">
            <h2 style="margin: 5px 0; font-size: 18px;">Daily Sales Report</h2>
            <p style="margin: 5px 0; color: #376F4B;">{{ $report->sale_date->format('l, F d, Y') }}</p>
        </div>
    </div>


    <!-- Report Info -->
    <div class="info-section">
        <table>
            <tr>
                <td class="info-label">Recorded By:</td>
                <td>{{ $report->user->name }}</td>
            </tr>
            <tr>
                <td class="info-label">Date Recorded:</td>
                <td>{{ $report->created_at->format('M d, Y h:i A') }}</td>
            </tr>
        </table>
    </div>

    <!-- Sales Items -->
    <h2>Sales Items</h2>
    <table class="items">
        <thead>
            <tr>
                <th>Product</th>
                <th class="text-center">Quantity</th>
                <th class="text-right">Unit Price</th>
                <th class="text-right">Total</th>
            </tr>
        </thead>
        <tbody>
            @foreach($lineItems as $item)
            <tr>
                <td>{{ $item->product_name }}</td>
                <td class="text-center">{{ $item->quantity }}</td>
                <td class="text-right">ZMW {{ number_format($item->unit_price, 2) }}</td>
                <td class="text-right">ZMW {{ number_format($item->total_price, 2) }}</td>
            </tr>
            @endforeach
            <tr class="totals-row">
                <td colspan="3" class="text-right">Total Sales Value:</td>
                <td class="text-right">ZMW {{ number_format($report->total_sales_value, 2) }}</td>
            </tr>
        </tbody>
    </table>

    <!-- Deductions -->
    @if($report->deductions->count() > 0)
    <h2>Deductions</h2>
    <table class="items">
        <thead>
            <tr>
                <th>Description</th>
                <th class="text-right" style="width: 150px;">Amount</th>
            </tr>
        </thead>
        <tbody>
            @foreach($report->deductions as $deduction)
            <tr>
                <td>{{ $deduction->description }}</td>
                <td class="text-right">ZMW {{ number_format($deduction->amount, 2) }}</td>
            </tr>
            @endforeach
            <tr class="totals-row">
                <td class="text-right">Total Deductions:</td>
                <td class="text-right">ZMW {{ number_format($report->total_deductions, 2) }}</td>
            </tr>
        </tbody>
    </table>
    @endif

    <!-- Summary -->
    <h2>Summary</h2>

    <table class="cards">
        <tr>
            <td class="card" style="width: 50%; background-color: #f9fafb; border: 1px solid #e5e7eb;">
                <div class="card-label" style="color: #6b7280;">Total Sales Value</div>
                <div class="card-value" style="color: #111827;">ZMW {{ number_format($report->total_sales_value, 2) }}</div>
            </td>
            <td class="card" style="width: 50%; background-color: #fef2f2; border: 1px solid #fecaca;">
                <div class="card-label" style="color: #b91c1c;">Total Deductions</div>
                <div class="card-value" style="color: #b91c1c;">ZMW {{ number_format($report->total_deductions, 2) }}</div>
            </td>
        </tr>
    </table>

    @if($report->isApproved())
    <table class="cards">
        <tr>
            <td class="card" style="width: 50%; background-color: #f0fdf4; border: 1px solid #bbf7d0;">
                <div class="card-label" style="color: #15803d;">Cash</div>
                <div class="card-value" style="color: #166534;">ZMW {{ number_format($report->total_cash, 2) }}</div>
            </td>
            <td class="card" style="width: 50%; background-color: #eff6ff; border: 1px solid #bfdbfe;">
                <div class="card-label" style="color: #1d4ed8;">Cash @ Bank</div>
                <div class="card-value" style="color: #1e40af;">ZMW {{ number_format($report->total_bank, 2) }}</div>
            </td>
        </tr>
        <tr>
            <td class="card" style="width: 50%; background-color: #fffbeb; border: 1px solid #fde68a;">
                <div class="card-label" style="color: #b45309;">Mobile Money</div>
                <div class="card-value" style="color: #92400e;">ZMW {{ number_format($report->total_mobile_money, 2) }}</div>
            </td>
            <td class="card" style="width: 50%; background-color: #fef2f2; border: 1px solid #fecaca;">
                <div class="card-label" style="color: #b91c1c;">Outstanding Debt</div>
                <div class="card-value" style="color: #b91c1c;">ZMW {{ number_format($report->total_outstanding, 2) }}</div>
            </td>
        </tr>
    </table>
    @endif

    <table class="cash-banner">
        <tr>
            <td style="font-size: 16px; font-weight: bold;">Cash at Hand</td>
            <td style="font-size: 22px; font-weight: bold; text-align: right;">ZMW {{ number_format($report->cash_at_hand, 2) }}</td>
        </tr>
    </table>


    <!-- Monthly Cumulative Sales - Admin Only -->
    @if(auth()->check() && auth()->user()->role === 'admin')
    <div style="margin-top: 30px; padding: 20px; background: linear-gradient(135deg, #f0fdf4 0%, #fef3c7 50%, #fee2e2 100%); border: 3px solid #f97316; border-radius: 8px; text-align: center; box-shadow: 0 4px 6px rgba(0,0,0,0.1);">
        <h2 style="margin: 0 0 10px 0; color: #c2410c; font-size: 14px; font-weight: bold;">
            📊 Total Monthly Sales ({{ $monthlyTotals['month_name'] }})
        </h2>
        <p style="margin: 10px 0; font-size: 32px; font-weight: bold; color: #ea580c;">
            ZMW {{ number_format($monthlyTotals['total_sales'], 2) }}
        </p>
        <p style="margin: 5px 0 0 0; font-size: 10px; color: #9a3412; font-weight: 600;">
            Cumulative from {{ \Carbon\Carbon::parse($monthlyTotals['start_date'])->format('M d') }} to {{ \Carbon\Carbon::parse($monthlyTotals['end_date'])->format('M d, Y') }} ({{ $monthlyTotals['report_count'] }} report(s))
        </p>
    </div>
    @endif

    <!-- Notes -->
    @if($report->notes)
    <h2>Notes</h2>
    <p>{{ $report->notes }}</p>
    @endif

    <!-- Footer -->
    <div class="footer">
        <p>Generated on {{ now()->format('F d, Y h:i A') }} | Sales Analytics System</p>
    </div>

</body>

</html>