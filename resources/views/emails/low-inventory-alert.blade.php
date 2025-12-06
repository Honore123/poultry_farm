<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Inventory Alert</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
            background-color: #f5f5f5;
        }
        .container {
            background: white;
            border-radius: 8px;
            padding: 30px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        h1 {
            color: #d97706;
            font-size: 24px;
            margin-bottom: 10px;
        }
        .date {
            color: #666;
            font-size: 14px;
            margin-bottom: 30px;
        }
        .section {
            margin-bottom: 25px;
            padding: 15px;
            border-radius: 6px;
        }
        .section-danger {
            background-color: #fef2f2;
            border-left: 4px solid #ef4444;
        }
        .section-warning {
            background-color: #fffbeb;
            border-left: 4px solid #f59e0b;
        }
        .section-info {
            background-color: #eff6ff;
            border-left: 4px solid #3b82f6;
        }
        .section h2 {
            font-size: 16px;
            margin: 0 0 15px 0;
        }
        .section-danger h2 { color: #dc2626; }
        .section-warning h2 { color: #d97706; }
        .section-info h2 { color: #2563eb; }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }
        th, td {
            padding: 10px;
            text-align: left;
            border-bottom: 1px solid #e5e7eb;
        }
        th {
            font-weight: 600;
            font-size: 12px;
            text-transform: uppercase;
            color: #6b7280;
        }
        .item-name {
            font-weight: 600;
            color: #1f2937;
        }
        .item-sku {
            font-size: 12px;
            color: #6b7280;
        }
        .qty {
            font-weight: 600;
        }
        .qty-zero { color: #dc2626; }
        .qty-critical { color: #d97706; }
        .qty-low { color: #3b82f6; }
        .category-badge {
            display: inline-block;
            padding: 2px 8px;
            border-radius: 4px;
            font-size: 11px;
            font-weight: 500;
            background: #f3f4f6;
            color: #4b5563;
        }
        .footer {
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #e5e7eb;
            font-size: 12px;
            color: #666;
            text-align: center;
        }
        .cta-button {
            display: inline-block;
            background: #d97706;
            color: white;
            padding: 12px 24px;
            text-decoration: none;
            border-radius: 6px;
            margin-top: 20px;
        }
        .summary-box {
            background: #f9fafb;
            border-radius: 6px;
            padding: 15px;
            margin-bottom: 20px;
            display: flex;
            justify-content: space-around;
            text-align: center;
        }
        .summary-item {
            padding: 0 15px;
        }
        .summary-number {
            font-size: 24px;
            font-weight: bold;
        }
        .summary-label {
            font-size: 12px;
            color: #6b7280;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>📦 Inventory Alert</h1>
        <p class="date">{{ now()->format('l, F j, Y \a\t H:i') }}</p>

        {{-- Summary Box --}}
        <div class="summary-box">
            <div class="summary-item">
                <div class="summary-number" style="color: #dc2626;">{{ count($outOfStockItems) }}</div>
                <div class="summary-label">Out of Stock</div>
            </div>
            <div class="summary-item">
                <div class="summary-number" style="color: #d97706;">{{ count($criticalStockItems) }}</div>
                <div class="summary-label">Critical</div>
            </div>
            <div class="summary-item">
                <div class="summary-number" style="color: #3b82f6;">{{ count($lowStockItems) }}</div>
                <div class="summary-label">Low Stock</div>
            </div>
        </div>

        @if(count($outOfStockItems) > 0)
            <div class="section section-danger">
                <h2>🚨 Out of Stock - Immediate Action Required</h2>
                <table>
                    <thead>
                        <tr>
                            <th>Item</th>
                            <th>Category</th>
                            <th>Stock</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($outOfStockItems as $stock)
                            <tr>
                                <td>
                                    <div class="item-name">{{ $stock['item']->name }}</div>
                                    <div class="item-sku">{{ $stock['item']->sku }}</div>
                                </td>
                                <td><span class="category-badge">{{ ucfirst($stock['category']) }}</span></td>
                                <td class="qty qty-zero">0 {{ $stock['uom'] }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif

        @if(count($criticalStockItems) > 0)
            <div class="section section-warning">
                <h2>⚠️ Critical Low Stock - Order Soon</h2>
                <table>
                    <thead>
                        <tr>
                            <th>Item</th>
                            <th>Category</th>
                            <th>Remaining</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($criticalStockItems as $stock)
                            <tr>
                                <td>
                                    <div class="item-name">{{ $stock['item']->name }}</div>
                                    <div class="item-sku">{{ $stock['item']->sku }}</div>
                                </td>
                                <td><span class="category-badge">{{ ucfirst($stock['category']) }}</span></td>
                                <td class="qty qty-critical">{{ number_format($stock['total_qty'], 2) }} {{ $stock['uom'] }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif

        @if(count($lowStockItems) > 0)
            <div class="section section-info">
                <h2>📦 Low Stock - Plan to Reorder</h2>
                <table>
                    <thead>
                        <tr>
                            <th>Item</th>
                            <th>Category</th>
                            <th>Remaining</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($lowStockItems as $stock)
                            <tr>
                                <td>
                                    <div class="item-name">{{ $stock['item']->name }}</div>
                                    <div class="item-sku">{{ $stock['item']->sku }}</div>
                                </td>
                                <td><span class="category-badge">{{ ucfirst($stock['category']) }}</span></td>
                                <td class="qty qty-low">{{ number_format($stock['total_qty'], 2) }} {{ $stock['uom'] }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif

        <div style="text-align: center;">
            <a href="{{ url('/admin/inventory-lots') }}" class="cta-button">View Inventory</a>
        </div>

        <div class="footer">
            <p>This is an automated inventory alert from your Poultry Farm Management System.</p>
            <p>Thresholds: Feed &lt;100kg (critical &lt;50kg) | Drugs &lt;20 units | Packaging &lt;100 pcs</p>
        </div>
    </div>
</body>
</html>

