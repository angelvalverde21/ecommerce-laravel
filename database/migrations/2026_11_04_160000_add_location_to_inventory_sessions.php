<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\{DB, Schema};

return new class extends Migration
{
    public function up(): void
    {
        $locations = [];
        foreach (DB::table('inventory_sessions')->get(['id', 'inventory_id']) as $session) {
            $ids = DB::table('inventory_counts')->where('inventory_session_id', $session->id)->distinct()->pluck('location_id');
            if ($ids->count() !== 1) throw new RuntimeException('La sesión '.$session->id.' debe tener una única ubicación identificable antes de migrar. No se han modificado sus conteos.');
            $locationId = (int) $ids->first();
            $storeId = DB::table('inventories')->where('id', $session->inventory_id)->value('store_id');
            if (!DB::table('locations')->where('id', $locationId)->where('store_id', $storeId)->exists()) throw new RuntimeException('La ubicación de la sesión '.$session->id.' no pertenece a su tienda.');
            $locations[$session->id] = $locationId;
        }
        Schema::table('inventory_sessions', function (Blueprint $table) {
            $table->foreignId('location_id')->nullable()->constrained('locations')->restrictOnDelete();
            $table->index(['inventory_id', 'location_id']);
        });
        foreach ($locations as $id => $locationId) DB::table('inventory_sessions')->where('id', $id)->update(['location_id' => $locationId]);
        Schema::table('inventory_sessions', fn (Blueprint $table) => $table->unsignedBigInteger('location_id')->nullable(false)->change());
    }

    public function down(): void
    {
        Schema::table('inventory_sessions', function (Blueprint $table) {
            $table->dropForeign(['location_id']);
            $table->dropIndex(['inventory_id', 'location_id']);
            $table->dropColumn('location_id');
        });
    }
};
