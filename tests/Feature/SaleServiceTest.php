<?php

namespace Tests\Feature;

use App\Enums\PaymentMethod;
use App\Models\DailySalesReport;
use App\Models\Product;
use App\Models\User;
use App\Services\SaleService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class SaleServiceTest extends TestCase
{
    use RefreshDatabase;

    private function service(): SaleService
    {
        return app(SaleService::class);
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function product(array $overrides = []): Product
    {
        return Product::create(array_merge([
            'name' => 'Panel',
            'sku' => 'SKU-'.fake()->unique()->numerify('#####'),
            'price' => 100,
            'cost' => 60,
            'stock_quantity' => 20,
            'track_stock' => true,
            'is_active' => true,
        ], $overrides));
    }

    public function test_recording_a_sale_persists_items_and_deducts_stock(): void
    {
        $user = User::factory()->create(['role' => 'sales_rep']);
        $product = $this->product(['stock_quantity' => 20]);

        $sale = $this->service()->record([
            'payment_method' => PaymentMethod::Cash->value,
            'items' => [
                ['product_id' => $product->id, 'product_name' => $product->name, 'quantity' => 3, 'unit_price' => 100],
            ],
        ], $user);

        $this->assertEquals(300, $sale->total_amount);
        $this->assertCount(1, $sale->items);
        $this->assertStringStartsWith('INV-', $sale->reference);

        $this->assertSame(17, $product->fresh()->stock_quantity); // 20 - 3
        $this->assertDatabaseHas('stock_movements', [
            'product_id' => $product->id,
            'type' => 'sale',
            'quantity' => -3,
        ]);
    }

    public function test_credit_sale_sets_amount_due_and_keeps_customer(): void
    {
        $user = User::factory()->create(['role' => 'sales_rep']);

        $sale = $this->service()->record([
            'payment_method' => PaymentMethod::Credit->value,
            'customer_name' => 'John Banda',
            'items' => [
                ['product_name' => 'Cable', 'quantity' => 2, 'unit_price' => 50],
            ],
        ], $user);

        $this->assertEquals(100, $sale->total_amount);
        $this->assertEquals(100, $sale->amount_due);
        $this->assertSame('John Banda', $sale->customer_name);
    }

    public function test_partial_payment_on_credit_sale_reduces_amount_due(): void
    {
        $user = User::factory()->create(['role' => 'sales_rep']);

        $sale = $this->service()->record([
            'payment_method' => PaymentMethod::Credit->value,
            'customer_name' => 'Mary Phiri',
            'paid_amount' => 40,
            'paid_via' => 'mobile_money',
            'items' => [
                ['product_name' => 'Cable', 'quantity' => 2, 'unit_price' => 50],
            ],
        ], $user);

        $this->assertEquals(100, $sale->total_amount);
        $this->assertEquals(40, $sale->paid_amount);
        $this->assertSame('mobile_money', $sale->paid_via);
        $this->assertEquals(60, $sale->amount_due); // 100 - 40
    }

    public function test_partial_payment_cannot_cover_the_full_invoice(): void
    {
        $user = User::factory()->create(['role' => 'sales_rep']);

        try {
            $this->service()->record([
                'payment_method' => PaymentMethod::Credit->value,
                'customer_name' => 'Mary Phiri',
                'paid_amount' => 100,
                'paid_via' => 'cash',
                'items' => [['product_name' => 'Cable', 'quantity' => 2, 'unit_price' => 50]],
            ], $user);
            $this->fail('Expected a ValidationException for a full "partial" payment.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('paid_amount', $e->errors());
        }

        $this->assertDatabaseCount('sales', 0);
    }

    public function test_paid_amount_is_ignored_for_non_credit_sales(): void
    {
        $user = User::factory()->create(['role' => 'sales_rep']);

        $sale = $this->service()->record([
            'payment_method' => PaymentMethod::Cash->value,
            'paid_amount' => 50,
            'paid_via' => 'bank',
            'items' => [['product_name' => 'X', 'quantity' => 1, 'unit_price' => 80]],
        ], $user);

        $this->assertEquals(0, $sale->paid_amount);
        $this->assertNull($sale->paid_via);
        $this->assertEquals(0, $sale->amount_due);
    }

    public function test_cash_sale_has_no_amount_due(): void
    {
        $user = User::factory()->create();

        $sale = $this->service()->record([
            'payment_method' => PaymentMethod::Cash->value,
            'items' => [['product_name' => 'X', 'quantity' => 1, 'unit_price' => 80]],
        ], $user);

        $this->assertEquals(0, $sale->amount_due);
    }

    public function test_out_of_stock_is_blocked_and_nothing_is_persisted(): void
    {
        $user = User::factory()->create(['role' => 'sales_rep']);
        $product = $this->product(['stock_quantity' => 2]);

        try {
            $this->service()->record([
                'payment_method' => PaymentMethod::Cash->value,
                'items' => [
                    ['product_id' => $product->id, 'product_name' => $product->name, 'quantity' => 5, 'unit_price' => 100],
                ],
            ], $user);
            $this->fail('Expected a ValidationException for insufficient stock.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('items', $e->errors());
        }

        $this->assertSame(2, $product->fresh()->stock_quantity); // untouched
        $this->assertDatabaseCount('sales', 0);
    }

    public function test_cannot_record_into_an_approved_day(): void
    {
        $user = User::factory()->create(['role' => 'sales_rep']);

        DailySalesReport::create([
            'user_id' => $user->id,
            'sale_date' => now()->toDateString(),
            'total_sales_value' => 0,
            'total_deductions' => 0,
            'cash_at_hand' => 0,
            'status' => 'completed',
            'approved_by' => $user->id,
            'approved_at' => now(),
        ]);

        $this->expectException(ValidationException::class);

        $this->service()->record([
            'payment_method' => PaymentMethod::Cash->value,
            'items' => [['product_name' => 'X', 'quantity' => 1, 'unit_price' => 10]],
        ], $user);
    }

    public function test_voiding_restores_stock(): void
    {
        $user = User::factory()->create(['role' => 'sales_rep']);
        $product = $this->product(['stock_quantity' => 10]);

        $sale = $this->service()->record([
            'payment_method' => PaymentMethod::Cash->value,
            'items' => [['product_id' => $product->id, 'product_name' => $product->name, 'quantity' => 4, 'unit_price' => 100]],
        ], $user);

        $this->assertSame(6, $product->fresh()->stock_quantity);

        $this->service()->void($sale);

        $this->assertSame('void', $sale->fresh()->status);
        $this->assertSame(10, $product->fresh()->stock_quantity); // restored
    }

    public function test_cannot_void_a_reconciled_sale(): void
    {
        $user = User::factory()->create(['role' => 'admin']);

        $report = DailySalesReport::create([
            'user_id' => $user->id,
            'sale_date' => now()->toDateString(),
            'total_sales_value' => 0,
            'total_deductions' => 0,
            'cash_at_hand' => 0,
            'status' => 'completed',
        ]);

        $sale = $this->service()->record([
            'payment_method' => PaymentMethod::Cash->value,
            'items' => [['product_name' => 'X', 'quantity' => 1, 'unit_price' => 10]],
        ], $user);
        $sale->update(['day_end_report_id' => $report->id]);

        $this->expectException(ValidationException::class);
        $this->service()->void($sale);
    }

    public function test_references_increment_per_day(): void
    {
        $user = User::factory()->create();

        $first = $this->service()->record([
            'payment_method' => PaymentMethod::Cash->value,
            'items' => [['product_name' => 'X', 'quantity' => 1, 'unit_price' => 10]],
        ], $user);

        $second = $this->service()->record([
            'payment_method' => PaymentMethod::Cash->value,
            'items' => [['product_name' => 'Y', 'quantity' => 1, 'unit_price' => 10]],
        ], $user);

        $this->assertStringEndsWith('-0001', $first->reference);
        $this->assertStringEndsWith('-0002', $second->reference);
    }
}
