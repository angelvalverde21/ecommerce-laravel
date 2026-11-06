<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\{DB, Schema};

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('inventory_counts', function (Blueprint $table) {
            $table->index(['inventory_session_id', 'location_id', 'variant_id'], 'inventory_counts_session_location_variant_index');
            $table->dropUnique('inventory_counts_session_location_variant_unique');
        });
    }

    public function down(): void
    {
        if (DB::table('inventory_counts')->select('inventory_session_id', 'location_id', 'variant_id')->groupBy('inventory_session_id', 'location_id', 'variant_id')->havingRaw('COUNT(*) > 1')->exists()) {
            throw new RuntimeException('No se puede restaurar la restriccion: existen capturas multiples. No se han eliminado registros.');
        }
        Schema::table('inventory_counts', function (Blueprint $table) {
            $table->unique(['inventory_session_id', 'location_id', 'variant_id'], 'inventory_counts_session_location_variant_unique');
            $table->dropIndex('inventory_counts_session_location_variant_index');
        });
    }
};
