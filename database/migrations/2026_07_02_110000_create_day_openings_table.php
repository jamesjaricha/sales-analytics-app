<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Additive (new table): the opening cash float for a trading day, captured
     * when the cashier first signs in — one row per business date.
     */
    public function up(): void
    {
        Schema::create('day_openings', function (Blueprint $table) {
            $table->id();
            $table->date('business_date')->unique();
            $table->decimal('opening_balance', 12, 2)->default(0);
            $table->foreignId('opened_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('day_openings');
    }
};
