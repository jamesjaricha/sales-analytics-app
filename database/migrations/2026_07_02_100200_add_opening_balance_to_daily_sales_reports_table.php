<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Additive: balance brought forward (opening cash float) entered at day-end.
     * Nullable so historical reports stay untouched.
     */
    public function up(): void
    {
        Schema::table('daily_sales_reports', function (Blueprint $table) {
            $table->decimal('opening_balance', 12, 2)->nullable()->after('counted_cash');
        });
    }

    public function down(): void
    {
        Schema::table('daily_sales_reports', function (Blueprint $table) {
            $table->dropColumn('opening_balance');
        });
    }
};
