<?php

namespace Tests\Feature;

use App\Enums\PaymentMethod;
use App\Models\Product;
use App\Models\Sale;
use App\Models\User;
use App\Services\SaleService;
use App\Services\StockService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ConcurrencySafetyTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create(['role' => 'sales_rep']);
    }

    public function test_reference_collision_retries_with_the_next_sequence(): void
    {
        // One existing sale whose reference already occupies the slot the
        // next sale would compute (count = 1 → next = 0002). This is exactly
        // the state a losing till sees after a same-instant collision.
        $datePart = now()->format('Ymd');
        Sale::create([
            'reference' => sprintf('INV-%s-0002', $datePart),
            'user_id' => $this->user->id,
            'business_date' => now()->toDateString(),
            'payment_method' => 'cash',
            'total_amount' => 100,
            'status' => 'completed',
        ]);

        $sale = app(SaleService::class)->record([
            'payment_method' => PaymentMethod::Cash->value,
            'items' => [['product_name' => 'Bulb', 'quantity' => 1, 'unit_price' => 50]],
        ], $this->user);

        $this->assertSame(sprintf('INV-%s-0003', $datePart), $sale->reference);
        $this->assertDatabaseCount('sales', 2);
    }

    public function test_stock_adjustment_reads_fresh_stock_not_the_stale_instance(): void
    {
        $product = Product::create([
            'name' => 'Panel',
            'sku' => 'SKU-CONC-1',
            'price' => 100,
            'cost' => 60,
            'stock_quantity' => 20,
            'track_stock' => true,
            'is_active' => true,
        ]);

        // Simulate another till selling 5 units after our instance was loaded
        $stale = Product::find($product->id);
        DB::table('products')->where('id', $product->id)->update(['stock_quantity' => 15]);

        $movement = app(StockService::class)->adjustStock(
            product: $stale,
            quantity: -3,
            type: 'sale',
            userId: $this->user->id,
        );

        // The adjustment must be based on the database value (15), not the
        // stale in-memory value (20) — otherwise the other till's deduction
        // would be silently overwritten.
        $this->assertEquals(15, $movement->stock_before);
        $this->assertEquals(12, $movement->stock_after);
        $this->assertEquals(12, $product->fresh()->stock_quantity);
        $this->assertEquals(12, $stale->stock_quantity); // caller instance synced
    }
}
