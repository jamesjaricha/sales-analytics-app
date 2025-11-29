<?php

namespace App\Services;

use App\Models\Product;
use App\Models\StockMovement;
use Illuminate\Support\Facades\DB;

class StockService
{
    /**
     * Adjust stock for a product
     *
     * @param Product $product
     * @param int $quantity Positive for increase, negative for decrease
     * @param string $type Movement type (in, out, adjustment, sale, purchase, return, initial)
     * @param int $userId User performing the action
     * @param string|null $notes Optional notes
     * @param int|null $referenceId Optional reference ID (e.g., sales item ID)
     * @param string|null $referenceType Optional reference type (e.g., DailySalesItem)
     * @param float|null $unitCost Optional unit cost
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
            // Get current stock before adjustment
            $stockBefore = $product->stock_quantity;

            // Calculate new stock
            $stockAfter = $stockBefore + $quantity;

            // Update product stock
            $product->stock_quantity = $stockAfter;
            $product->save();

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
     * @param Product $product
     * @param int $quantity
     * @param int $userId
     * @param int $salesItemId
     * @return StockMovement|null Returns null if stock tracking is disabled
     */
    public function processSale(
        Product $product,
        int $quantity,
        int $userId,
        int $salesItemId
    ) {
        // Only process if stock tracking is enabled
        if (!$product->track_stock) {
            return null;
        }

        return $this->adjustStock(
            product: $product,
            quantity: -$quantity, // Negative for stock decrease
            type: 'sale',
            userId: $userId,
            notes: "Stock deducted for sale",
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
     * Get stock movement summary for a date range
     *
     * @param string $startDate
     * @param string $endDate
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
