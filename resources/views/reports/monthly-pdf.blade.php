<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Monthly Sales Report - {{ $analytics['month_name'] }}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 11px;
            line-height: 1.4;
            color: #333;
            margin: 20px;
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
            border-bottom: 3px solid #ea580c;
            padding-bottom: 15px;
        }
        .header h1 {
            margin: 0;
            font-size: 26px;
            color: #000;
            background: linear-gradient(135deg, #16a34a 0%, #ea580c 50%, #dc2626 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        .header p {
            margin: 5px 0;
            color: #666;
            font-size: 12px;
        }
        .metrics-grid {
            display: table;
            width: 100%;
            margin-bottom: 25px;
        }
        .metric-box {
            display: table-cell;
            width: 25%;
            padding: 15px;
            text-align: center;
            border: 2px solid #e5e7eb;
            background: #f9fafb;
        }
        .metric-box h3 {
            margin: 0 0 5px 0;
            font-size: 9px;
            color: #6b7280;
            text-transform: uppercase;
        }
        .metric-box p {
            margin: 0;
            font-size: 20px;
            font-weight: bold;
            color: #111827;
        }
        .section {
            margin-bottom: 25px;
            page-break-inside: avoid;
        }
        .section-title {
            font-size: 16px;
            font-weight: bold;
            margin-bottom: 12px;
            color: #111827;
            border-bottom: 2px solid #ea580c;
            padding-bottom: 5px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
        }
        table th {
            background-color: #f3f4f6;
            border: 1px solid #d1d5db;
            padding: 8px;
            text-align: left;
            font-weight: bold;
            font-size: 10px;
        }
        table td {
            border: 1px solid #d1d5db;
            padding: 8px;
            font-size: 10px;
        }
        .rank-badge {
            display: inline-block;
            width: 25px;
            height: 25px;
            border-radius: 50%;
            text-align: center;
            line-height: 25px;
            font-weight: bold;
            font-size: 11px;
        }
        .rank-1 { background: #fef3c7; color: #92400e; }
        .rank-2 { background: #e5e7eb; color: #374151; }
        .rank-3 { background: #fed7aa; color: #9a3412; }
        .rank-other { background: #f3f4f6; color: #6b7280; }
        .best-day {
            padding: 10px;
            margin-bottom: 8px;
            background: linear-gradient(135deg, #f0fdf4 0%, #dbeafe 100%);
            border: 1px solid #86efac;
            border-radius: 6px;
        }
        .best-day-header {
            font-weight: bold;
            font-size: 11px;
            color: #047857;
        }
        .best-day-amount {
            font-size: 16px;
            font-weight: bold;
            color: #059669;
            float: right;
        }
        .insights-grid {
            display: table;
            width: 100%;
        }
        .insight-box {
            display: table-cell;
            width: 50%;
            padding: 12px;
            border: 2px solid #c7d2fe;
            background: #eef2ff;
            margin-bottom: 10px;
        }
        .insight-title {
            font-weight: bold;
            font-size: 11px;
            color: #3730a3;
            margin-bottom: 5px;
        }
        .insight-text {
            font-size: 10px;
            color: #4338ca;
        }
        .day-grid {
            display: table;
            width: 100%;
            margin-bottom: 15px;
        }
        .day-cell {
            display: table-cell;
            width: 14.28%;
            text-align: center;
            padding: 10px 5px;
            border: 1px solid #dbeafe;
            background: #eff6ff;
        }
        .day-name {
            font-size: 9px;
            font-weight: bold;
            color: #1e40af;
        }
        .day-count {
            font-size: 16px;
            font-weight: bold;
            color: #2563eb;
            margin: 5px 0;
        }
        .day-avg {
            font-size: 9px;
            color: #059669;
        }
        .footer {
            margin-top: 30px;
            padding-top: 10px;
            border-top: 1px solid #ccc;
            font-size: 9px;
            color: #666;
            text-align: center;
        }
        .page-break {
            page-break-after: always;
        }
    </style>
</head>
<body>

    <!-- Header -->
    <div class="header">
        <h1>Monthly Sales Analytics Report</h1>
        <p style="font-size: 14px; font-weight: bold; color: #ea580c;">{{ $analytics['month_name'] }}</p>
        <p>{{ $analytics['start_date'] }} - {{ $analytics['end_date'] }}</p>
    </div>

    <!-- Key Metrics -->
    <div class="metrics-grid">
        <div class="metric-box">
            <h3>Total Sales</h3>
            <p style="color: #2563eb;">ZMW {{ number_format($analytics['total_sales'], 2) }}</p>
            <p style="font-size: 9px; color: #6b7280;">{{ $analytics['report_count'] }} reports</p>
        </div>
        <div class="metric-box">
            <h3>Avg. Daily Sales</h3>
            <p style="color: #059669;">ZMW {{ number_format($analytics['average_daily_sales'], 2) }}</p>
            <p style="font-size: 9px; color: #6b7280;">Per report</p>
        </div>
        <div class="metric-box" style="border-right: none;">
            <h3>Reports Filed</h3>
            <p style="color: #7c3aed;">{{ $analytics['report_count'] }}</p>
            <p style="font-size: 9px; color: #6b7280;">Completed</p>
        </div>
    </div>

    <!-- Top Products -->
    <div class="section">
        <div class="section-title">Top Performing Products</div>
        <table>
            <thead>
                <tr>
                    <th style="width: 10%; text-align: center;">Rank</th>
                    <th style="width: 50%;">Product Name</th>
                    <th style="width: 20%; text-align: center;">Units Sold</th>
                    <th style="width: 20%; text-align: right;">Total Revenue</th>
                </tr>
            </thead>
            <tbody>
                @forelse($analytics['top_products'] as $index => $product)
                    <tr>
                        <td style="text-align: center;">
                            <span class="rank-badge {{ $index === 0 ? 'rank-1' : ($index === 1 ? 'rank-2' : ($index === 2 ? 'rank-3' : 'rank-other')) }}">
                                {{ $index + 1 }}
                            </span>
                        </td>
                        <td style="font-weight: {{ $index < 3 ? 'bold' : 'normal' }};">{{ $product->product_name }}</td>
                        <td style="text-align: center; font-weight: bold; color: #2563eb;">{{ $product->total_quantity }} units</td>
                        <td style="text-align: right; font-weight: bold;">ZMW {{ number_format($product->total_revenue, 2) }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" style="text-align: center; color: #6b7280;">No product data available</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <!-- Best Days -->
    <div class="section">
        <div class="section-title">Top 5 Sales Days</div>
        @forelse($analytics['best_days'] as $index => $day)
            <div class="best-day">
                <span style="font-size: 16px; font-weight: bold; color: #047857; float: left; margin-right: 10px;">{{ $index + 1 }}</span>
                <div class="best-day-header">{{ $day->sale_date->format('l, F d, Y') }}</div>
                <div style="font-size: 9px; color: #6b7280;">Recorded by {{ $day->user->name }}</div>
                <div class="best-day-amount">ZMW {{ number_format($day->total_sales_value, 2) }}</div>
                <div style="clear: both;"></div>
            </div>
        @empty
            <p style="text-align: center; color: #6b7280;">No sales data available</p>
        @endforelse
    </div>

    <div class="page-break"></div>

    <!-- Weekly Performance -->
    @if($analytics['weekly_performance']->isNotEmpty())
        <div class="section">
            <div class="section-title">Weekly Breakdown</div>
            <table>
                <thead>
                    <tr>
                        <th>Week</th>
                        <th style="text-align: center;">Reports</th>
                        <th style="text-align: right;">Total Sales</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($analytics['weekly_performance'] as $week => $data)
                        <tr>
                            <td style="font-weight: bold;">{{ $week }}</td>
                            <td style="text-align: center;">{{ $data['count'] }}</td>
                            <td style="text-align: right; font-weight: bold; color: #7c3aed;">ZMW {{ number_format($data['total_sales'], 2) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif

    <!-- Insights -->
    <div class="section">
        <div class="section-title">Key Insights</div>
        <table style="border: none;">
            @foreach($analytics['insights']->chunk(2) as $insightRow)
                <tr>
                    @foreach($insightRow as $insight)
                        <td style="width: 50%; padding: 12px; border: 2px solid #c7d2fe; background: #eef2ff; vertical-align: top;">
                            <div class="insight-title">{{ $insight['title'] }}</div>
                            <div class="insight-text">{{ $insight['description'] }}</div>
                        </td>
                    @endforeach
                    @if($insightRow->count() === 1)
                        <td style="width: 50%; border: none;"></td>
                    @endif
                </tr>
            @endforeach
        </table>
    </div>

    <!-- Footer -->
    <div class="footer">
        <p>Generated on {{ now()->format('F d, Y h:i A') }} | Sales Analytics System</p>
    </div>

</body>
</html>
