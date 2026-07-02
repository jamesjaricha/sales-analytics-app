<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Additive (new table): payments received against outstanding credit
     * invoices. Each payment reduces the invoice's amount_due and is settled
     * into the day-end of the day it was RECEIVED (day_end_report_id).
     */
    public function up(): void
    {
        Schema::create('debt_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sale_id')->constrained('sales')->cascadeOnDelete();
            $table->decimal('amount', 12, 2);
            $table->string('payment_method', 20)->default('cash');
            $table->date('business_date');
            $table->foreignId('received_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('note')->nullable();
            $table->foreignId('day_end_report_id')->nullable()->constrained('daily_sales_reports')->nullOnDelete();
            $table->timestamps();

            $table->index(['business_date', 'day_end_report_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('debt_payments');
    }
};
