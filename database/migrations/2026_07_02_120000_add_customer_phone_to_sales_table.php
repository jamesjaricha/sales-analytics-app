<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Additive: debtor phone number, required for new credit sales
     * (nullable so historical rows stay valid).
     */
    public function up(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->string('customer_phone', 30)->nullable()->after('customer_name');
        });
    }

    public function down(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->dropColumn('customer_phone');
        });
    }
};
