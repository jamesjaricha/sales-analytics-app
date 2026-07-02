<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Additive: expenses can be paid from cash, bank, or mobile money.
     * Default 'cash' keeps every existing row semantically correct.
     */
    public function up(): void
    {
        Schema::table('deductions', function (Blueprint $table) {
            $table->string('payment_method', 20)->default('cash')->after('amount');
        });
    }

    public function down(): void
    {
        Schema::table('deductions', function (Blueprint $table) {
            $table->dropColumn('payment_method');
        });
    }
};
