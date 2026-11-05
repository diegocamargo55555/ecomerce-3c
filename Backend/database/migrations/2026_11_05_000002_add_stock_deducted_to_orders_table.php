<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Orders created by the original CRUD did not deduct stock.
        Schema::table('orders', fn (Blueprint $table) => $table->boolean('stock_deducted')->default(false));
    }

    public function down(): void
    {
        Schema::table('orders', fn (Blueprint $table) => $table->dropColumn('stock_deducted'));
    }
};
