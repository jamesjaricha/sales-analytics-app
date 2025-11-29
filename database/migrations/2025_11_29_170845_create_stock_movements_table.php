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
        Schema::create('stock_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');

            // Type: in, out, adjustment, sale, purchase, return
            $table->enum('type', ['in', 'out', 'adjustment', 'sale', 'purchase', 'return', 'initial']);

            // Quantity (positive for in, negative for out)
            $table->integer('quantity');

            // Stock before and after for audit trail
            $table->integer('stock_before');
            $table->integer('stock_after');

            // Reference to related record (sales_item_id, etc.)
            $table->unsignedBigInteger('reference_id')->nullable();
            $table->string('reference_type')->nullable(); // DailySalesItem, Purchase, etc.

            // Notes/reason for movement
            $table->text('notes')->nullable();

            // Cost per unit at time of movement
            $table->decimal('unit_cost', 15, 2)->nullable();

            $table->timestamps();

            // Indexes for better performance
            $table->index(['product_id', 'created_at']);
            $table->index('type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stock_movements');
    }
};
