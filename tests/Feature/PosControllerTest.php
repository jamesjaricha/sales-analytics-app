<?php

namespace Tests\Feature;

use App\Enums\PaymentMethod;
use App\Models\Product;
use App\Models\Sale;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PosControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_sales_rep_can_record_an_invoice(): void
    {
        $rep = User::factory()->create(['role' => 'sales_rep']);
        $product = Product::create([
            'name' => 'Inverter',
            'sku' => 'INV-1',
            'price' => 500,
            'stock_quantity' => 5,
            'track_stock' => true,
            'is_active' => true,
        ]);

        $response = $this->actingAs($rep)->post('/pos', [
            'payment_method' => PaymentMethod::Cash->value,
            'items' => [
                ['product_id' => $product->id, 'product_name' => 'Inverter', 'quantity' => 1, 'unit_price' => 500],
            ],
        ]);

        $response->assertRedirect(route('pos.create'));
        $response->assertSessionHas('success');
        $this->assertDatabaseHas('sales', [
            'user_id' => $rep->id,
            'payment_method' => 'cash',
            'status' => 'completed',
        ]);
        $this->assertSame(4, $product->fresh()->stock_quantity);
    }

    public function test_invoice_requires_at_least_one_item(): void
    {
        $rep = User::factory()->create(['role' => 'sales_rep']);

        $response = $this->actingAs($rep)->post('/pos', [
            'payment_method' => PaymentMethod::Cash->value,
            'items' => [],
        ]);

        $response->assertSessionHasErrors('items');
        $this->assertDatabaseCount('sales', 0);
    }

    public function test_credit_sale_requires_a_customer_name(): void
    {
        $rep = User::factory()->create(['role' => 'sales_rep']);

        $response = $this->actingAs($rep)->post('/pos', [
            'payment_method' => PaymentMethod::Credit->value,
            'items' => [['product_name' => 'X', 'quantity' => 1, 'unit_price' => 10]],
        ]);

        $response->assertSessionHasErrors('customer_name');
    }

    public function test_a_sale_can_be_voided(): void
    {
        $rep = User::factory()->create(['role' => 'sales_rep']);

        $this->actingAs($rep)->post('/pos', [
            'payment_method' => PaymentMethod::Cash->value,
            'items' => [['product_name' => 'X', 'quantity' => 1, 'unit_price' => 10]],
        ]);

        $sale = Sale::first();

        $this->actingAs($rep)->post("/pos/{$sale->id}/void")
            ->assertSessionHas('success');

        $this->assertSame('void', $sale->fresh()->status);
    }

    public function test_guests_cannot_record(): void
    {
        $this->post('/pos', [
            'payment_method' => 'cash',
            'items' => [['product_name' => 'X', 'quantity' => 1, 'unit_price' => 10]],
        ])->assertRedirect();

        $this->assertDatabaseCount('sales', 0);
    }
}
