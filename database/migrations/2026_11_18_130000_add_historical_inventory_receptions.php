<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('inventory_receptions', function (Blueprint $table) {
            $table->boolean('is_historical')->default(false);
            $table->boolean('stock_applied')->default(true);
        });
    }
    public function down(): void
    {
        Schema::table('inventory_receptions', fn (Blueprint $table) => $table->dropColumn(['is_historical', 'stock_applied']));
    }
};
