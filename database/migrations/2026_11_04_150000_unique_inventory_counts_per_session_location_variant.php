<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\{DB, Schema};

return new class extends Migration
{
    public function up(): void
    {
        $duplicate = DB::table('inventory_counts')
            ->select('inventory_session_id', 'location_id', 'variant_id')
            ->groupBy('inventory_session_id', 'location_id', 'variant_id')
            ->havingRaw('COUNT(*) > 1')->first();

        if ($duplicate) {
            throw new RuntimeException('Existen conteos duplicados por sesión, ubicación y variante. Revisa esos registros antes de aplicar la restricción; no se ha eliminado ningún conteo.');
        }

        Schema::table('inventory_counts', function (Blueprint $table) {
            $table->unique(['inventory_session_id', 'location_id', 'variant_id'], 'inventory_counts_session_location_variant_unique');
            $table->dropIndex('inventory_counts_session_location_variant_index');
        });
    }

    public function down(): void
    {
        Schema::table('inventory_counts', function (Blueprint $table) {
            $table->index(['inventory_session_id', 'location_id', 'variant_id'], 'inventory_counts_session_location_variant_index');
            $table->dropUnique('inventory_counts_session_location_variant_unique');
        });
    }
};
