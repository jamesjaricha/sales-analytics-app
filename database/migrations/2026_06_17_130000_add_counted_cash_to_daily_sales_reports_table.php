<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Physically counted cash recorded at day-end (for variance vs expected).
     * Additive + nullable.
     */
    public function up(): void
    {
        Schema::table('daily_sales_reports', function (Blueprint $table) {
            $table->decimal('counted_cash', 12, 2)->nullable()->after('total_outstanding');
        });
    }

    public function down(): void
    {
        Schema::table('daily_sales_reports', function (Blueprint $table) {
            $table->dropColumn('counted_cash');
        });
    }
};
