<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            // Add low stock threshold (alert when stock falls below this)
            $table->integer('low_stock_threshold')->default(10)->after('stock_quantity');

            // Track reorder level
            $table->integer('reorder_level')->nullable()->after('low_stock_threshold');

            // Track reorder quantity
            $table->integer('reorder_quantity')->nullable()->after('reorder_level');

            // Unit of measurement
            $table->string('unit_of_measurement', 50)->default('pcs')->after('reorder_quantity');

            // Enable/disable stock tracking for this product
            $table->boolean('track_stock')->default(true)->after('unit_of_measurement');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn([
                'low_stock_threshold',
                'reorder_level',
                'reorder_quantity',
                'unit_of_measurement',
                'track_stock'
            ]);
        });
    }
};
