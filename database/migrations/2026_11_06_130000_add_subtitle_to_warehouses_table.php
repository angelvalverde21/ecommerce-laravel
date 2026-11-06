<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('warehouses', function (Blueprint $table) {
            $table->string('subtitle')->nullable();
        });

        DB::table('warehouses')->orderBy('id')->each(function (object $warehouse) {
            if (preg_match('/^(.+?)\s*\(([^()]+)\)\s*$/u', $warehouse->name, $matches)) {
                DB::table('warehouses')->where('id', $warehouse->id)->update([
                    'name' => trim($matches[1]),
                    'subtitle' => trim($matches[2]),
                ]);
            }
        });
    }

    public function down(): void
    {
        DB::table('warehouses')->whereNotNull('subtitle')->orderBy('id')->each(function (object $warehouse) {
            DB::table('warehouses')->where('id', $warehouse->id)->update([
                'name' => $warehouse->name . ' (' . $warehouse->subtitle . ')',
            ]);
        });

        Schema::table('warehouses', function (Blueprint $table) {
            $table->dropColumn('subtitle');
        });
    }
};
