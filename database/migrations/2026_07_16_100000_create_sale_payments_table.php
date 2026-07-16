<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tender lines for split-payment invoices — one row per method the
     * customer paid with (e.g. cash 300 + mobile money 200 on one invoice).
     * Single-method sales keep working off sales.payment_method and have
     * no rows here, so this is purely additive.
     */
    public function up(): void
    {
        Schema::create('sale_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sale_id')->constrained()->cascadeOnDelete();
            $table->string('method');                 // cash | bank | mobile_money
            $table->decimal('amount', 12, 2);
            $table->timestamps();

            $table->index('method');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sale_payments');
    }
};
