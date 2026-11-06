<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\{DB, Schema};

return new class extends Migration
{
    public function up(): void
    {
        $assignments = [];
        foreach (DB::table('inventory_sessions')->get(['id', 'inventory_id', 'location_id']) as $session) {
            $location = DB::table('locations')->where('id', $session->location_id)->first();
            $storeId = DB::table('inventories')->where('id', $session->inventory_id)->value('store_id');
            if (!$location || (int) $location->store_id !== (int) $storeId) throw new RuntimeException('No se puede identificar el almacén de la sesión '.$session->id.'.');
            $foreign = DB::table('inventory_counts')->join('locations', 'locations.id', '=', 'inventory_counts.location_id')
                ->where('inventory_session_id', $session->id)->where('locations.warehouse_id', '!=', $location->warehouse_id)->exists();
            if ($foreign) throw new RuntimeException('La sesión '.$session->id.' tiene conteos en varios almacenes. No se han modificado sus registros.');
            $assignments[$session->id] = $location->warehouse_id;
        }
        Schema::table('inventory_sessions', fn (Blueprint $table) => $table->foreignId('warehouse_id')->nullable()->constrained('warehouses')->restrictOnDelete());
        foreach ($assignments as $id => $warehouseId) DB::table('inventory_sessions')->where('id', $id)->update(['warehouse_id' => $warehouseId]);
        Schema::table('inventory_sessions', function (Blueprint $table) {
            $table->unsignedBigInteger('warehouse_id')->nullable(false)->change();
            $table->index(['inventory_id', 'warehouse_id']);
            $table->dropForeign(['location_id']);
            $table->dropIndex(['inventory_id', 'location_id']);
            $table->dropColumn('location_id');
        });
    }

    public function down(): void
    {
        $assignments = [];
        foreach (DB::table('inventory_sessions')->get(['id']) as $session) {
            $ids = DB::table('inventory_counts')->where('inventory_session_id', $session->id)->distinct()->pluck('location_id');
            if ($ids->count() !== 1) throw new RuntimeException('No se puede restaurar una única ubicación para la sesión '.$session->id.'.');
            $assignments[$session->id] = $ids->first();
        }
        Schema::table('inventory_sessions', fn (Blueprint $table) => $table->foreignId('location_id')->nullable()->constrained('locations')->restrictOnDelete());
        foreach ($assignments as $id => $locationId) DB::table('inventory_sessions')->where('id', $id)->update(['location_id' => $locationId]);
        Schema::table('inventory_sessions', function (Blueprint $table) {
            $table->unsignedBigInteger('location_id')->nullable(false)->change();
            $table->index(['inventory_id', 'location_id']);
            $table->dropForeign(['warehouse_id']);
            $table->dropIndex(['inventory_id', 'warehouse_id']);
            $table->dropColumn('warehouse_id');
        });
    }
};
