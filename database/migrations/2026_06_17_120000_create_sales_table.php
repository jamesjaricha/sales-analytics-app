<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * POS invoices — one row per sale recorded during the trading day.
     */
    public function up(): void
    {
        Schema::create('sales', function (Blueprint $table) {
            $table->id();
            $table->string('reference')->unique();           // e.g. INV-20260617-0007
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->date('business_date');                   // trading day it belongs to
            $table->string('payment_method');                // cash | bank | mobile_money | credit
            $table->decimal('total_amount', 12, 2);
            $table->decimal('amount_due', 12, 2)->default(0); // outstanding (credit only)
            $table->string('customer_name')->nullable();      // optional; required when credit
            $table->text('note')->nullable();
            $table->string('status')->default('completed');   // completed | void
            $table->foreignId('day_end_report_id')->nullable()
                ->constrained('daily_sales_reports')->nullOnDelete(); // set on approval → locks the invoice
            $table->timestamps();

            $table->index(['business_date', 'status']);
            $table->index('payment_method');
            $table->index('day_end_report_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sales');
    }
};
