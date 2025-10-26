<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('daily_sales_reports', function (Blueprint $table) {
            $table->enum('status', ['draft', 'completed'])->default('completed')->after('cash_at_hand');
        });
    }

    public function down()
    {
        Schema::table('daily_sales_reports', function (Blueprint $table) {
            $table->dropColumn('status');
        });
    }
};
