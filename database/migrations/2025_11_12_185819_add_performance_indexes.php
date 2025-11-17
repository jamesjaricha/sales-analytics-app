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
        Schema::table('daily_sales_reports', function (Blueprint $table) {
            $table->index(['status', 'sale_date'], 'idx_status_sale_date');
            $table->index(['user_id', 'sale_date'], 'idx_user_sale_date');
            $table->index('sale_date', 'idx_sale_date');
        });

        Schema::table('daily_sales_items', function (Blueprint $table) {
            $table->index('daily_sales_report_id', 'idx_daily_sales_report_id');
            $table->index('product_name', 'idx_product_name');
        });

        Schema::table('products', function (Blueprint $table) {
            $table->index('sku', 'idx_sku');
            $table->index('is_active', 'idx_is_active');
            $table->index('category', 'idx_category');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->index('role', 'idx_role');
        });

        Schema::table('sales_report_drafts', function (Blueprint $table) {
            $table->index(['user_id', 'sale_date'], 'idx_user_sale_date_drafts');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('daily_sales_reports', function (Blueprint $table) {
            $table->dropIndex('idx_status_sale_date');
            $table->dropIndex('idx_user_sale_date');
            $table->dropIndex('idx_sale_date');
        });

        Schema::table('daily_sales_items', function (Blueprint $table) {
            $table->dropIndex('idx_daily_sales_report_id');
            $table->dropIndex('idx_product_name');
        });

        Schema::table('products', function (Blueprint $table) {
            $table->dropIndex('idx_sku');
            $table->dropIndex('idx_is_active');
            $table->dropIndex('idx_category');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex('idx_role');
        });

        Schema::table('sales_report_drafts', function (Blueprint $table) {
            $table->dropIndex('idx_user_sale_date_drafts');
        });
    }
};
