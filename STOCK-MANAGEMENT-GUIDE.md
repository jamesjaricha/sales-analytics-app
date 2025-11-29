# Stock Management Module - Quick Reference

## Overview

The Stock Management module provides comprehensive inventory tracking with audit trails, low stock alerts, and integration with the sales system.

## Features Implemented

### 1. Database Structure

-   **Products Table Enhancements:**

    -   `low_stock_threshold` - Alert trigger level (default: 10)
    -   `reorder_level` - When to reorder stock
    -   `reorder_quantity` - How much to reorder
    -   `unit_of_measurement` - Unit type (pcs, kg, L, etc.)
    -   `track_stock` - Enable/disable tracking per product

-   **Stock Movements Table:**
    -   Complete audit trail of all stock changes
    -   Types: in, out, adjustment, sale, purchase, return, initial
    -   Records: quantity, stock before/after, user, timestamp
    -   Polymorphic references to link movements to sales items
    -   Unit cost tracking for valuation

### 2. Routes

```
GET  /stock                    - Stock dashboard
GET  /stock/adjust             - Stock adjustment form
POST /stock/adjust             - Process adjustment
GET  /stock/low-stock          - Low stock alerts
GET  /stock/reports            - Stock reports
GET  /stock/{product}/history  - Movement history
```

### 3. Views Created

1. **stock/index.blade.php** - Main dashboard

    - Stock summary cards (total value, low stock, out of stock)
    - Alerts for low/out of stock products
    - Product list with current stock levels
    - Color-coded status indicators

2. **stock/adjust.blade.php** - Stock adjustment form

    - Product selection with current stock display
    - Movement type selector
    - Quantity input
    - Optional unit cost and notes
    - Real-time product info display

3. **stock/history.blade.php** - Movement history

    - Product details header
    - Complete movement log
    - Color-coded movement types
    - Before/after stock levels
    - User tracking

4. **stock/low-stock.blade.php** - Low stock alerts

    - Filtered view of products needing attention
    - Reorder information display
    - Quick access to adjust stock
    - Out of stock highlighted in red

5. **stock/reports.blade.php** - Stock reports
    - Date range filter
    - Summary cards (total value, movements, in/out)
    - Movement breakdown by type
    - Recent movements table

### 4. Models & Services

**Product Model Enhancements:**

-   `stockMovements()` - Relationship to stock movements
-   `isLowStock()` - Check if below threshold
-   `isOutOfStock()` - Check if at/below zero
-   `stock_status` - Computed attribute (in_stock, low_stock, out_of_stock, not_tracked)
-   `stock_status_color` - Badge color for UI
-   `scopeLowStock()` - Query scope for low stock products
-   `scopeOutOfStock()` - Query scope for out of stock products

**StockMovement Model:**

-   Relationships: Product, User, polymorphic reference
-   Type scopes: `ofType($type)`
-   Date filtering: `dateRange($startDate, $endDate)`
-   Badge colors and labels for UI display

**StockService:**

-   `adjustStock()` - Main method to adjust stock with audit trail
-   `processSale()` - Auto-deduct stock for sales
-   `getLowStockProducts()` - Retrieve low stock items
-   `getOutOfStockProducts()` - Retrieve out of stock items
-   `getTotalStockValue()` - Calculate total inventory value
-   `getMovementSummary()` - Get movement statistics for date range

### 5. Navigation

-   Added "Stock" link to main navigation
-   Accessible to both admin and sales_rep roles

## Usage Guide

### Initial Stock Setup

1. Go to Stock → Adjust Stock
2. Select product
3. Choose "Initial Stock" type
4. Enter quantity
5. Optionally enter unit cost
6. Submit

### Daily Stock Adjustments

1. Navigate to Stock → Adjust Stock
2. Select product and movement type:
    - **Stock In** - General increase
    - **Stock Out** - General decrease
    - **Purchase** - Received from supplier
    - **Return** - Customer returned items
    - **Adjustment** - Corrections (damage, theft, etc.)
3. Enter quantity (always positive)
4. Add notes explaining the adjustment
5. Submit

### Monitoring Stock Levels

1. **Dashboard View** (Stock → Stock Management)

    - See all products with current stock
    - View alerts for low/out of stock
    - Check total stock value

2. **Low Stock Alerts** (Stock → Low Stock)

    - Filtered view of products needing attention
    - Shows reorder levels and quantities
    - Quick access to adjustment form

3. **Movement History** (Click "History" next to any product)
    - Complete audit trail
    - Who made changes and when
    - Stock levels before and after each movement

### Generating Reports

1. Go to Stock → Reports
2. Set date range
3. View:
    - Total movements
    - Stock in/out summary
    - Breakdown by type (purchases, sales, returns, adjustments)
    - Recent movements table

## Integration with Sales System

### Automatic Stock Deduction (To Be Implemented)

When a sale is recorded through the sales form, the system will:

1. Check if product has `track_stock` enabled
2. Deduct quantity from `stock_quantity`
3. Create a StockMovement record with type='sale'
4. Link movement to the sales item
5. Record user and timestamp

### Implementation Steps

To integrate automatic stock deduction with sales:

1. Update `DailySalesController@store()` method
2. After creating each sales item, call:
    ```php
    if ($product->track_stock) {
        app(StockService::class)->processSale(
            product: $product,
            quantity: $quantity,
            userId: auth()->id(),
            salesItemId: $salesItem->id
        );
    }
    ```

## Stock Movement Types

| Type       | Effect    | Use Case                        |
| ---------- | --------- | ------------------------------- |
| in         | +quantity | General stock increase          |
| out        | -quantity | General stock decrease          |
| purchase   | +quantity | Stock received from supplier    |
| sale       | -quantity | Stock sold to customer (auto)   |
| return     | +quantity | Customer returned product       |
| adjustment | -quantity | Corrections (damage, loss)      |
| initial    | +quantity | Setting up initial stock levels |

## Low Stock Alerts

The system automatically flags products as:

-   **Out of Stock**: `stock_quantity <= 0`
-   **Low Stock**: `stock_quantity <= low_stock_threshold`

Configure per-product:

-   Low stock threshold (when to alert)
-   Reorder level (when to place order)
-   Reorder quantity (how much to order)

## Security & Permissions

-   All stock management routes require authentication
-   Both 'admin' and 'sales_rep' roles have access
-   All movements are tracked with user ID
-   Complete audit trail prevents tampering
-   Throttling applied to prevent abuse

## Best Practices

1. **Always Add Notes**: When adjusting stock, explain why
2. **Use Correct Types**: Choose the appropriate movement type
3. **Regular Audits**: Check movement history regularly
4. **Set Thresholds**: Configure low stock alerts for critical items
5. **Initial Setup**: Use "Initial Stock" type for first-time stock entry
6. **Check Reports**: Review weekly to spot trends

## Files Modified/Created

### Migrations

-   `2025_11_29_170831_add_stock_management_to_products_table.php`
-   `2025_11_29_170845_create_stock_movements_table.php`

### Models

-   `app/Models/Product.php` (enhanced)
-   `app/Models/StockMovement.php` (new)

### Controllers

-   `app/Http/Controllers/StockController.php` (new)

### Services

-   `app/Services/StockService.php` (new)

### Views

-   `resources/views/stock/index.blade.php`
-   `resources/views/stock/adjust.blade.php`
-   `resources/views/stock/history.blade.php`
-   `resources/views/stock/low-stock.blade.php`
-   `resources/views/stock/reports.blade.php`

### Routes

-   `routes/web.php` (added stock routes)

### Layouts

-   `resources/views/layouts/app.blade.php` (added navigation link)

## Next Steps (Optional Enhancements)

1. **Integrate with Sales**: Auto-deduct stock when sales are recorded
2. **Email Alerts**: Notify when stock is low
3. **Stock Forecasting**: Predict when to reorder based on sales trends
4. **Barcode Scanning**: Quick stock adjustments via barcode
5. **Multi-location**: Track stock across multiple warehouses
6. **Stock Transfers**: Move stock between locations
7. **Supplier Management**: Track which supplier provides which product
8. **Purchase Orders**: Generate POs when stock is low
9. **Stock Takes**: Physical count vs system comparison
10. **Export Reports**: PDF/Excel export of stock reports
