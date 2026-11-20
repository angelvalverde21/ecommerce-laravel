<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!app()->environment('testing')) {
            $directory = storage_path('app/private/backups/production-section-selection-'.now()->format('Ymd-His'));
            File::ensureDirectoryExists($directory);
            File::put($directory.'/before.json', json_encode([
                'progress' => DB::table('production_stage_progress')->get(),
                'events' => DB::table('production_stage_events')->get(),
                'purchases' => DB::table('purchases')->get(['id', 'production_stage_id']),
            ], JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR));
        }
        Schema::table('production_stage_progress', fn (Blueprint $table) => $table->boolean('is_enabled')->default(true));
        Schema::table('manufactures', fn (Blueprint $table) => $table->unsignedInteger('stage_configuration_revision')->default(0));
        // Older productions retain all defaults; subsequent creations persist only their selection.
        $ids = DB::table('production_stages')->pluck('id');
        DB::table('manufactures')->where('type', 'production')->orderBy('id')->chunkById(100, function ($productions) use ($ids) {
            foreach ($productions as $production) foreach ($ids as $id) {
                DB::table('production_stage_progress')->insertOrIgnore([
                    'manufacture_id' => $production->id, 'stage_id' => $id, 'is_enabled' => true,
                    'status' => 'pending', 'revision' => 0, 'created_at' => now(), 'updated_at' => now(),
                ]);
            }
        });
    }

    public function down(): void
    {
        // Removing selection flags could expose sections deliberately disabled by the user.
        throw new RuntimeException('Conserva la selección por producción mediante una migración correctiva.');
    }
};
