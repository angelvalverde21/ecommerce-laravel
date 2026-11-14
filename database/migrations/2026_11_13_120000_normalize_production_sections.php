<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const SECTIONS = [
        ['molds', 'Moldes y ploteo', 'moldes-y-ploteo', true, false],
        ['samples', 'Muestras', 'muestras', true, true],
        ['materials', 'Telas e insumos', 'telas-e-insumos', true, false],
        ['cutting', 'Corte', 'corte', true, false],
        ['sewing', 'Costura', 'costura', true, true],
        ['finishing', 'Acabados', 'acabados', true, false],
        ['others', 'Otros', 'otros', false, false],
    ];

    public function up(): void
    {
        // MySQL commits DDL separately. Resume the final index change without remapping purchases twice.
        if (Schema::hasTable('production_stage_progress') && Schema::hasColumn('production_stages', 'show_in_stepper')) {
            $purchaseKey = collect(Schema::getForeignKeys('purchases'))->first(fn ($key) => $key['columns'] === ['production_stage_id']);
            if (($purchaseKey['foreign_table'] ?? null) !== 'production_stages'
                || DB::table('production_stage_progress')->whereNull('stage_id')->exists()) {
                throw new RuntimeException('La normalización parcial debe revisarse antes de continuar.');
            }
            $this->finishNormalization();

            return;
        }
        $unknown = DB::table('production_stages')->whereNotIn('code', array_column(self::SECTIONS, 0))->exists();
        if ($unknown) {
            throw new RuntimeException('Hay etapas personalizadas que deben revisarse antes de normalizar.');
        }
        if (! app()->environment('testing')) {
            $directory = storage_path('app/private/backups/production-sections-'.now()->format('Ymd-His'));
            File::ensureDirectoryExists($directory);
            File::put($directory.'/before.json', json_encode([
                'stages' => DB::table('production_stages')->get(),
                'events' => DB::table('production_stage_events')->get(),
                'purchase_stages' => DB::table('purchases')->get(['id', 'production_stage_id']),
            ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR));
        }

        Schema::rename('production_stages', 'production_stage_progress');
        Schema::create('production_stages', function (Blueprint $table) {
            $table->id();
            $table->string('code', 40)->unique();
            $table->string('name');
            $table->string('slug', 80)->unique();
            $table->unsignedTinyInteger('position');
            $table->boolean('show_in_stepper')->default(true);
            $table->boolean('delivery_tracking_default')->default(false);
            $table->timestamps();
        });
        foreach (self::SECTIONS as $position => [$code, $name, $slug, $visible, $delivery]) {
            DB::table('production_stages')->insert([
                'id' => $position + 1, 'code' => $code, 'name' => $name, 'slug' => $slug, 'position' => $position,
                'show_in_stepper' => $visible, 'delivery_tracking_default' => $delivery,
                'created_at' => now(), 'updated_at' => now(),
            ]);
        }
        Schema::table('production_stage_progress', function (Blueprint $table) {
            $table->foreignId('stage_id')->nullable()->constrained('production_stages')->restrictOnDelete();
        });
        foreach (DB::table('production_stages')->get() as $section) {
            DB::table('production_stage_progress')->where('code', $section->code)->update(['stage_id' => $section->id]);
        }
        Schema::table('purchases', fn (Blueprint $table) => $table->dropForeign(['production_stage_id']));
        DB::statement('UPDATE purchases SET production_stage_id = (SELECT stage_id FROM production_stage_progress WHERE production_stage_progress.id = purchases.production_stage_id) WHERE production_stage_id IS NOT NULL');
        Schema::table('purchases', fn (Blueprint $table) => $table->foreign('production_stage_id')->references('id')->on('production_stages')->restrictOnDelete());
        $this->finishNormalization();
    }

    private function finishNormalization(): void
    {
        $indexes = collect(Schema::getIndexes('production_stage_progress'))->pluck('name');
        // The manufacture foreign key needs an index with manufacture_id first at all times.
        if (! $indexes->contains('production_stage_progress_owner_unique')) {
            Schema::table('production_stage_progress', fn (Blueprint $table) => $table->unique(['manufacture_id', 'stage_id'], 'production_stage_progress_owner_unique'));
        }
        if ($indexes->contains('production_stages_manufacture_id_code_unique')) {
            Schema::table('production_stage_progress', fn (Blueprint $table) => $table->dropUnique('production_stages_manufacture_id_code_unique'));
        }
        if (Schema::hasColumn('production_stage_progress', 'code')) {
            Schema::table('production_stage_progress', fn (Blueprint $table) => $table->dropColumn(['code', 'name', 'position']));
        }
    }

    public function down(): void
    {
        // Rebuild the former shape while keeping progress IDs and event references.
        Schema::table('production_stage_progress', function (Blueprint $table) {
            $table->string('code', 40)->nullable();
            $table->string('name')->nullable();
            $table->unsignedTinyInteger('position')->nullable();
        });
        foreach (DB::table('production_stages')->get() as $section) {
            DB::table('production_stage_progress')->where('stage_id', $section->id)->update(['code' => $section->code, 'name' => $section->name, 'position' => $section->position]);
        }
        // A purchase in an untouched section still needs an owner-specific row in the old schema.
        $owners = DB::table('purchases')->whereNotNull('production_stage_id')->where('purchaseable_type', \App\Models\Manufacture::class)->get(['purchaseable_id', 'production_stage_id'])->unique(fn ($row) => $row->purchaseable_id.':'.$row->production_stage_id);
        foreach ($owners as $owner) {
            if (! DB::table('production_stage_progress')->where('manufacture_id', $owner->purchaseable_id)->where('stage_id', $owner->production_stage_id)->exists()) {
                $section = DB::table('production_stages')->find($owner->production_stage_id);
                DB::table('production_stage_progress')->insert(['manufacture_id' => $owner->purchaseable_id, 'stage_id' => $section->id, 'code' => $section->code, 'name' => $section->name, 'position' => $section->position, 'created_at' => now(), 'updated_at' => now()]);
            }
        }
        Schema::table('purchases', fn (Blueprint $table) => $table->dropForeign(['production_stage_id']));
        DB::statement('UPDATE purchases SET production_stage_id = (SELECT id FROM production_stage_progress WHERE production_stage_progress.stage_id = purchases.production_stage_id AND production_stage_progress.manufacture_id = purchases.purchaseable_id) WHERE production_stage_id IS NOT NULL');
        Schema::table('production_stage_progress', fn (Blueprint $table) => $table->unique(['manufacture_id', 'code'], 'production_stages_manufacture_id_code_unique'));
        Schema::table('production_stage_progress', function (Blueprint $table) {
            $table->dropForeign(['stage_id']);
            $table->dropUnique('production_stage_progress_owner_unique');
            $table->dropColumn('stage_id');
        });
        Schema::drop('production_stages');
        Schema::rename('production_stage_progress', 'production_stages');
        Schema::table('purchases', fn (Blueprint $table) => $table->foreign('production_stage_id')->references('id')->on('production_stages')->restrictOnDelete());
    }
};
