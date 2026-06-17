<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Turn daily_sales_reports into the day-end reconciliation header:
     * settlement breakdown + approval metadata. Purely additive — the existing
     * `status` enum is left untouched (a report is "approved" iff approved_at is set).
     */
    public function up(): void
    {
        Schema::table('daily_sales_reports', function (Blueprint $table) {
            $table->decimal('total_cash', 12, 2)->default(0)->after('total_deductions');
            $table->decimal('total_bank', 12, 2)->default(0)->after('total_cash');
            $table->decimal('total_mobile_money', 12, 2)->default(0)->after('total_bank');
            $table->decimal('total_outstanding', 12, 2)->default(0)->after('total_mobile_money');
            $table->foreignId('approved_by')->nullable()->after('status')
                ->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable()->after('approved_by');
        });
    }

    public function down(): void
    {
        Schema::table('daily_sales_reports', function (Blueprint $table) {
            $table->dropConstrainedForeignId('approved_by');
            $table->dropColumn([
                'total_cash',
                'total_bank',
                'total_mobile_money',
                'total_outstanding',
                'approved_at',
            ]);
        });
    }
};
