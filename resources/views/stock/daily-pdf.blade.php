<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Daily Stock Report - {{ $date }}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 12px;
            line-height: 1.4;
            color: #333;
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
            border-bottom: 2px solid #333;
            padding-bottom: 15px;
        }
        .header h1 {
            margin: 0;
            font-size: 24px;
            color: #1a1a1a;
        }
        .header p {
            margin: 5px 0 0 0;
            color: #666;
            font-size: 14px;
        }
        .summary-boxes {
            display: table;
            width: 100%;
            margin-bottom: 25px;
        }
        .summary-box {
            display: table-cell;
            width: 25%;
            padding: 15px;
            text-align: center;
            border: 1px solid #ddd;
            background-color: #f9f9f9;
        }
        .summary-box .label {
            font-size: 10px;
            color: #666;
            text-transform: uppercase;
            margin-bottom: 5px;
        }
        .summary-box .value {
            font-size: 18px;
            font-weight: bold;
            color: #333;
        }
        .section-title {
            font-size: 16px;
            font-weight: bold;
            margin: 25px 0 10px 0;
            padding-bottom: 5px;
            border-bottom: 1px solid #ddd;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        th {
            background-color: #f5f5f5;
            padding: 8px;
            text-align: left;
            font-weight: bold;
            font-size: 11px;
            border: 1px solid #ddd;
        }
        td {
            padding: 6px 8px;
            border: 1px solid #ddd;
            font-size: 11px;
        }
        .text-right {
            text-align: right;
        }
        .text-center {
            text-align: center;
        }
        .status-badge {
            padding: 3px 8px;
            border-radius: 3px;
            font-size: 10px;
            font-weight: bold;
        }
        .status-in-stock {
            background-color: #d4edda;
            color: #155724;
        }
        .status-low-stock {
            background-color: #fff3cd;
            color: #856404;
        }
        .status-out-of-stock {
            background-color: #f8d7da;
            color: #721c24;
        }
        .movement-in {
            color: #28a745;
            font-weight: bold;
        }
        .movement-out {
            color: #dc3545;
            font-weight: bold;
        }
        .footer {
            margin-top: 30px;
            padding-top: 15px;
            border-top: 1px solid #ddd;
            font-size: 10px;
            color: #666;
            text-align: center;
        }
    </style>
</head>
<body>
    <!-- Header -->
    <div class="header">
        <h1>Daily Stock Report</h1>
        <p>{{ \Carbon\Carbon::parse($date)->format('l, F d, Y') }}</p>
    </div>

    <!-- Summary Boxes -->
    <div class="summary-boxes">
        <div class="summary-box">
            <div class="label">Total Stock Value</div>
            <div class="value">ZMW {{ number_format($totalStockValue, 2) }}</div>
        </div>
        <div class="summary-box">
            <div class="label">Total Movements</div>
            <div class="value">{{ $movementSummary['total_movements'] }}</div>
        </div>
        <div class="summary-box">
            <div class="label">Low Stock Items</div>
            <div class="value">{{ $lowStockCount }}</div>
        </div>
        <div class="summary-box">
            <div class="label">Out of Stock</div>
            <div class="value">{{ $outOfStockCount }}</div>
        </div>
    </div>

    <!-- Movement Summary -->
    @if($movements->count() > 0)
    <div class="section-title">Stock Movements Today</div>
    <table>
        <thead>
            <tr>
                <th>Time</th>
                <th>Product</th>
                <th>Type</th>
                <th class="text-right">Quantity</th>
                <th class="text-right">Before</th>
                <th class="text-right">After</th>
                <th>User</th>
                <th>Notes</th>
            </tr>
        </thead>
        <tbody>
            @foreach($movements as $movement)
            <tr>
                <td>{{ $movement->created_at->format('H:i') }}</td>
                <td>{{ $movement->product->name }}</td>
                <td>{{ $movement->type_label }}</td>
                <td class="text-right {{ $movement->quantity > 0 ? 'movement-in' : 'movement-out' }}">
                    {{ $movement->quantity > 0 ? '+' : '' }}{{ $movement->quantity }}
                </td>
                <td class="text-right">{{ $movement->stock_before }}</td>
                <td class="text-right">{{ $movement->stock_after }}</td>
                <td>{{ $movement->user->name ?? 'System' }}</td>
                <td>{{ $movement->notes ?? '-' }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <div style="margin: 15px 0; padding: 10px; background-color: #f9f9f9; border: 1px solid #ddd;">
        <strong>Movement Summary:</strong>
        Stock In: <span class="movement-in">+{{ $movementSummary['stock_in'] }}</span> |
        Stock Out: <span class="movement-out">-{{ $movementSummary['stock_out'] }}</span>
    </div>
    @else
    <div class="section-title">Stock Movements Today</div>
    <p style="text-align: center; color: #999; padding: 20px;">No stock movements recorded for this date.</p>
    @endif

    <!-- Current Stock Levels -->
    <div class="section-title">Current Stock Levels</div>
    <table>
        <thead>
            <tr>
                <th>Product</th>
                <th>SKU</th>
                <th class="text-center">Current Stock</th>
                <th class="text-center">Threshold</th>
                <th class="text-center">Status</th>
                <th>Unit</th>
                <th class="text-right">Value (ZMW)</th>
            </tr>
        </thead>
        <tbody>
            @foreach($products as $product)
            <tr>
                <td>{{ $product->name }}</td>
                <td>{{ $product->sku ?? '-' }}</td>
                <td class="text-center">{{ $product->stock_quantity }}</td>
                <td class="text-center">{{ $product->low_stock_threshold }}</td>
                <td class="text-center">
                    @if($product->stock_status === 'out_of_stock')
                        <span class="status-badge status-out-of-stock">Out of Stock</span>
                    @elseif($product->stock_status === 'low_stock')
                        <span class="status-badge status-low-stock">Low Stock</span>
                    @else
                        <span class="status-badge status-in-stock">In Stock</span>
                    @endif
                </td>
                <td>{{ $product->unit_of_measurement }}</td>
                <td class="text-right">{{ number_format($product->stock_quantity * (($product->cost > 0) ? $product->cost : $product->price), 2) }}</td>
            </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr style="background-color: #f5f5f5; font-weight: bold;">
                <td colspan="6" class="text-right">Total Stock Value:</td>
                <td class="text-right">ZMW {{ number_format($totalStockValue, 2) }}</td>
            </tr>
        </tfoot>
    </table>

    <!-- Footer -->
    <div class="footer">
        <p>Generated on {{ now()->format('l, F d, Y \a\t H:i') }} | Sales Analytics System</p>
    </div>
</body>
</html>
