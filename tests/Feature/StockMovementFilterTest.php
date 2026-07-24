<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\StockMovement;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StockMovementFilterTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Product $product;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create(['role' => 'admin']);
        $this->product = Product::create([
            'name' => 'Solar Panel',
            'sku' => 'SP-1',
            'price' => 100,
            'cost' => 60,
            'stock_quantity' => 50,
            'track_stock' => true,
            'is_active' => true,
        ]);
    }

    private function movement(string $type, int $qty, ?string $when = null): StockMovement
    {
        $m = StockMovement::create([
            'product_id' => $this->product->id,
            'user_id' => $this->user->id,
            'type' => $type,
            'quantity' => $qty,
            'stock_before' => 50,
            'stock_after' => 50 + $qty,
            'notes' => $type.' note',
        ]);

        if ($when) {
            $m->forceFill(['created_at' => $when])->save();
        }

        return $m;
    }

    public function test_reports_filters_movements_by_type(): void
    {
        $this->movement('sale', -3);
        $this->movement('purchase', 10);

        $response = $this->actingAs($this->user)->get(route('stock.reports', ['type' => 'purchase', 'period' => 'all']));

        $response->assertOk();
        $movements = $response->viewData('recentMovements');
        $this->assertCount(1, $movements);
        $this->assertSame('purchase', $movements->first()->type);
    }

    public function test_reports_filters_movements_by_date_preset(): void
    {
        $this->movement('sale', -1, now()->toDateTimeString());
        $this->movement('sale', -2, now()->subMonths(2)->toDateTimeString());

        $response = $this->actingAs($this->user)->get(route('stock.reports', ['period' => 'today']));

        $response->assertOk();
        $this->assertCount(1, $response->viewData('recentMovements'));
    }

    public function test_history_filters_by_type(): void
    {
        $this->movement('sale', -3);
        $this->movement('adjustment', -1);

        $response = $this->actingAs($this->user)
            ->get(route('stock.history', ['product' => $this->product, 'type' => 'adjustment']));

        $response->assertOk();
        $movements = $response->viewData('movements');
        $this->assertCount(1, $movements);
        $this->assertSame('adjustment', $movements->first()->type);
    }

    public function test_export_streams_csv_with_filtered_rows(): void
    {
        $this->movement('sale', -3);
        $this->movement('purchase', 10);

        $response = $this->actingAs($this->user)
            ->get(route('stock.movements.export', ['type' => 'purchase', 'period' => 'all']));

        $response->assertOk();
        $this->assertStringContainsString('text/csv', $response->headers->get('content-type'));
        $this->assertStringContainsString('attachment', $response->headers->get('content-disposition'));

        $csv = $response->streamedContent();
        $this->assertStringContainsString('Date,Product,SKU,Type,Quantity', $csv);
        $this->assertStringContainsString('Solar Panel', $csv);
        $this->assertStringContainsString('Purchase', $csv);
        $this->assertStringNotContainsString('Sale', $csv); // filtered out
    }

    public function test_guests_cannot_view_or_export_stock_movements(): void
    {
        $this->get(route('stock.reports'))->assertRedirect(route('login'));
        $this->get(route('stock.movements.export'))->assertRedirect(route('login'));
    }

    public function test_malformed_filters_are_rejected_not_a_500(): void
    {
        $this->actingAs($this->user)
            ->from(route('stock.reports'))
            ->get(route('stock.movements.export', ['period' => 'custom', 'start_date' => 'garbage']))
            ->assertRedirect(route('stock.reports'))
            ->assertSessionHasErrors('start_date');

        $this->actingAs($this->user)
            ->from(route('stock.reports'))
            ->get(route('stock.reports', ['type' => 'not-a-type']))
            ->assertRedirect(route('stock.reports'))
            ->assertSessionHasErrors('type');
    }

    public function test_export_includes_every_row_even_with_identical_timestamps(): void
    {
        // Rows sharing one created_at second must not be skipped or doubled
        // across chunk boundaries (unique id ordering guards this).
        $when = now()->toDateTimeString();
        for ($i = 0; $i < 7; $i++) {
            $this->movement('sale', -1, $when);
        }

        $csv = $this->actingAs($this->user)
            ->get(route('stock.movements.export', ['period' => 'all']))
            ->streamedContent();

        $this->assertSame(7, substr_count($csv, 'Solar Panel'));
    }
}
