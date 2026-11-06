<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('options', function (Blueprint $table) {
            $table->timestamp('archived_at')->nullable()->index();
            $table->string('active_name')->nullable()->storedAs('CASE WHEN archived_at IS NULL THEN name ELSE NULL END');
            $table->dropUnique(['store_id', 'product_id', 'name']);
            $table->unique(['store_id', 'product_id', 'active_name'], 'options_active_name_unique');
        });
    }

    public function down(): void
    {
        if (DB::table('options')->select('store_id', 'product_id', 'name')
            ->groupBy('store_id', 'product_id', 'name')->havingRaw('COUNT(*) > 1')->exists()) {
            throw new RuntimeException('No se puede revertir mientras existan opciones activas y archivadas con el mismo nombre.');
        }
        Schema::table('options', function (Blueprint $table) {
            $table->dropUnique('options_active_name_unique');
            $table->dropColumn(['active_name', 'archived_at']);
            $table->unique(['store_id', 'product_id', 'name']);
        });
    }
};
