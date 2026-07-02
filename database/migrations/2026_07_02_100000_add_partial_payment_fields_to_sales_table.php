<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Additive: partial payments on credit invoices.
     * paid_amount — portion of a credit invoice settled at the till.
     * paid_via — how that portion was paid (cash|bank|mobile_money).
     */
    public function up(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->decimal('paid_amount', 12, 2)->default(0)->after('amount_due');
            $table->string('paid_via', 20)->nullable()->after('paid_amount');
        });
    }

    public function down(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->dropColumn(['paid_amount', 'paid_via']);
        });
    }
};
