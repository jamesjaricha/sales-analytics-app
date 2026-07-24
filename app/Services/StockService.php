<?php

namespace App\Services;

use App\Models\Product;
use App\Models\StockMovement;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class StockService
{
    /**
     * Adjust stock for a product
     *
     * @param  int  $quantity  Positive for increase, negative for decrease
     * @param  string  $type  Movement type (in, out, adjustment, sale, purchase, return, initial)
     * @param  int  $userId  User performing the action
     * @param  string|null  $notes  Optional notes
     * @param  int|null  $referenceId  Optional reference ID (e.g., sales item ID)
     * @param  string|null  $referenceType  Optional reference type (e.g., DailySalesItem)
     * @param  float|null  $unitCost  Optional unit cost
     * @return StockMovement
     */
    public function adjustStock(
        Product $product,
        int $quantity,
        string $type,
        int $userId,
        ?string $notes = null,
        ?int $referenceId = null,
        ?string $referenceType = null,
        ?float $unitCost = null
    ) {
        return DB::transaction(function () use (
            $product,
            $quantity,
            $type,
            $userId,
            $notes,
            $referenceId,
            $referenceType,
            $unitCost
        ) {
            // Re-read under a row lock: the caller's instance may be stale when
            // two tills sell the same product at once, and a stale read here
            // would lose a deduction and falsify stock_before/stock_after.
            $locked = Product::whereKey($product->getKey())->lockForUpdate()->firstOrFail();

            $stockBefore = $locked->stock_quantity;
            $stockAfter = $stockBefore + $quantity;

            $locked->stock_quantity = $stockAfter;
            $locked->save();

            // Keep the caller's instance in step with what was persisted
            $product->stock_quantity = $stockAfter;
            $product->syncOriginalAttribute('stock_quantity');

            // Create stock movement record
            $movement = StockMovement::create([
                'product_id' => $product->id,
                'user_id' => $userId,
                'type' => $type,
                'quantity' => $quantity,
                'stock_before' => $stockBefore,
                'stock_after' => $stockAfter,
                'reference_id' => $referenceId,
                'reference_type' => $referenceType,
                'notes' => $notes,
                'unit_cost' => $unitCost,
            ]);

            return $movement;
        });
    }

    /**
     * Process stock for a sale
     *
     * @return StockMovement|null Returns null if stock tracking is disabled
     */
    public function processSale(
        Product $product,
        int $quantity,
        int $userId,
        int $salesItemId
    ) {
        // Only process if stock tracking is enabled
        if (! $product->track_stock) {
            return null;
        }

        return $this->adjustStock(
            product: $product,
            quantity: -$quantity, // Negative for stock decrease
            type: 'sale',
            userId: $userId,
            notes: 'Stock deducted for sale',
            referenceId: $salesItemId,
            referenceType: 'App\Models\DailySalesItem',
            unitCost: $product->cost
        );
    }

    /**
     * Get low stock products
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getLowStockProducts()
    {
        return Product::lowStock()
            ->where('is_active', true)
            ->orderBy('stock_quantity', 'asc')
            ->get();
    }

    /**
     * Get out of stock products
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getOutOfStockProducts()
    {
        return Product::outOfStock()
            ->where('is_active', true)
            ->orderBy('name')
            ->get();
    }

    /**
     * Get total stock value
     *
     * @return float
     */
    public function getTotalStockValue()
    {
        return Product::where('track_stock', true)
            ->where('is_active', true)
            ->selectRaw('SUM(stock_quantity * COALESCE(NULLIF(cost, 0), price, 0)) as total_value')
            ->value('total_value') ?? 0;
    }

    /**
     * Reconciliation totals for one product over a window: units sold,
     * received (stock in + purchases), returned, net adjustment, and the net
     * change overall. Sale rows are stored negative, so `sold` is flipped
     * back to a positive count.
     *
     * @return array{sold: int, received: int, returned: int, adjusted: int, net: int}
     */
    public function getProductPeriodSummary(Product $product, Carbon $start, Carbon $end): array
    {
        $row = $product->stockMovements()
            ->whereBetween('created_at', [$start, $end])
            ->selectRaw("
                COALESCE(SUM(CASE WHEN type = 'sale' THEN -quantity ELSE 0 END), 0) as sold,
                COALESCE(SUM(CASE WHEN type IN ('in', 'purchase') THEN quantity ELSE 0 END), 0) as received,
                COALESCE(SUM(CASE WHEN type = 'return' THEN quantity ELSE 0 END), 0) as returned,
                COALESCE(SUM(CASE WHEN type = 'adjustment' THEN quantity ELSE 0 END), 0) as adjusted,
                COALESCE(SUM(quantity), 0) as net
            ")
            ->first();

        return [
            'sold' => (int) $row->sold,
            'received' => (int) $row->received,
            'returned' => (int) $row->returned,
            'adjusted' => (int) $row->adjusted,
            'net' => (int) $row->net,
        ];
    }

    /**
     * Get stock movement summary for a date range
     *
     * @return array
     */
    public function getMovementSummary(string $startDate, string $endDate)
    {
        $movements = StockMovement::dateRange($startDate, $endDate)
            ->with('product')
            ->get();

        return [
            'total_movements' => $movements->count(),
            'stock_in' => $movements->where('type', 'in')->sum('quantity'),
            'stock_out' => $movements->where('type', 'out')->sum('quantity'),
            'sales' => $movements->where('type', 'sale')->sum('quantity'),
            'purchases' => $movements->where('type', 'purchase')->sum('quantity'),
            'adjustments' => $movements->where('type', 'adjustment')->sum('quantity'),
            'returns' => $movements->where('type', 'return')->sum('quantity'),
        ];
    }
}
