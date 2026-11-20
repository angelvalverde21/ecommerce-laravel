<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (!app()->environment('testing')) {
            $directory = storage_path('app/private/backups/production-workflow-'.now()->format('Ymd-His'));
            \Illuminate\Support\Facades\File::ensureDirectoryExists($directory);
            \Illuminate\Support\Facades\File::put($directory.'/before.json', json_encode([
                'stages' => DB::table('production_stages')->get(),
                'progress' => DB::table('production_stage_progress')->get(),
                'events' => DB::table('production_stage_events')->get(),
                'purchases' => DB::table('purchases')->get(['id', 'production_stage_id']),
            ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR));
        }
        DB::transaction(function () {
            DB::table('production_stages')->updateOrInsert(['code' => 'reception'], [
                'name' => 'Recepción de mercadería', 'slug' => 'recepcion', 'position' => 6,
                'show_in_stepper' => true, 'delivery_tracking_default' => false,
                'created_at' => now(), 'updated_at' => now(),
            ]);
            DB::table('production_stages')->where('code', 'others')->update([
                'position' => 7, 'show_in_stepper' => true, 'updated_at' => now(),
            ]);
            $stages = DB::table('production_stages')->pluck('id');
            DB::table('manufactures')->where('type', 'production')->orderBy('id')->chunkById(100, function ($productions) use ($stages) {
                foreach ($productions as $production) {
                    foreach ($stages as $stageId) {
                        DB::table('production_stage_progress')->insertOrIgnore([
                            'manufacture_id' => $production->id, 'stage_id' => $stageId,
                            'status' => 'pending', 'revision' => 0, 'created_at' => now(), 'updated_at' => now(),
                        ]);
                    }
                }
            });
        });
    }

    public function down(): void
    {
        // Preserve progress, purchases and events: this data migration is intentionally forward-only.
        throw new RuntimeException('Esta migración conserva avances e historial. Revierte mediante una migración correctiva.');
    }
};
