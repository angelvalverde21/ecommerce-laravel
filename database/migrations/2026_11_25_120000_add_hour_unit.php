<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        // Fresh installations are populated in order by UnitSeeder (Unidad remains ID 1).
        if (!DB::table('units')->exists()) return;
        if (DB::table('units')->where('singular', 'hora')->orWhere('abbreviation', 'h')->exists()) return;
        DB::table('units')->insert(['name' => 'Hora(s)', 'label' => 'Hora(s)', 'singular' => 'hora',
            'plural' => 'horas', 'abbreviation' => 'h', 'type' => 'time', 'created_at' => now(), 'updated_at' => now()]);
    }
    public function down(): void
    {
        // Preserve master data that may already be referenced by purchases.
    }
};
