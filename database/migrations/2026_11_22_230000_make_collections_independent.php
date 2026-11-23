<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // The original ERP collections table is an unused id/timestamps placeholder.
        // Preserve the IDs of the operational collections and every dependent record.
        if (Schema::hasTable('collections') && DB::table('collections')->exists()) {
            throw new RuntimeException('Revisa las colecciones existentes antes de trasladar las colecciones del proyecto.');
        }
        if (!Schema::hasTable('collections')) Schema::create('collections', function (Blueprint $t) { $t->id(); $t->timestamps(); });
        Schema::table('collections', function (Blueprint $t) {
            $t->foreignId('store_id')->nullable()->constrained()->restrictOnDelete();
            $t->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $t->string('name', 180)->nullable(); $t->text('description')->nullable();
            $t->string('status', 30)->default('planning');
            $t->date('starts_on')->nullable(); $t->date('ends_on')->nullable(); $t->date('launch_on')->nullable();
            $t->index(['store_id', 'status']);
        });
        foreach (DB::table('projects_collections')->orderBy('id')->get() as $collection) {
            $project = DB::table('projects')->where('id', $collection->project_id)->first();
            if (!$project) throw new RuntimeException('Hay una colección sin proyecto de origen.');
            DB::table('collections')->insert([
                'id' => $collection->id, 'store_id' => $project->store_id, 'created_by' => $project->created_by,
                'name' => $collection->name, 'description' => $collection->description ?? $project->description,
                'status' => $project->status, 'starts_on' => $project->starts_on, 'ends_on' => $project->ends_on,
                'launch_on' => $collection->launch_on, 'created_at' => $collection->created_at, 'updated_at' => $collection->updated_at,
            ]);
        }
        // A project without children still becomes a collection; do not discard it.
        foreach (DB::table('projects')->whereNotIn('id', DB::table('projects_collections')->select('project_id'))->get() as $project) {
            DB::table('collections')->insert([
                'store_id' => $project->store_id, 'created_by' => $project->created_by, 'name' => $project->name,
                'description' => $project->description, 'status' => $project->status,
                'starts_on' => $project->starts_on, 'ends_on' => $project->ends_on,
                'created_at' => $project->created_at, 'updated_at' => $project->updated_at,
            ]);
        }
        foreach (['projects_processes', 'projects_products'] as $table) {
            Schema::table($table, fn (Blueprint $t) => $t->dropForeign(['collection_id']));
            Schema::table($table, fn (Blueprint $t) => $t->foreign('collection_id')->references('id')->on('collections')->restrictOnDelete());
        }
        foreach (['processes', 'products', 'sales', 'receipts'] as $suffix) Schema::rename('projects_'.$suffix, 'collections_'.$suffix);
        Schema::drop('projects_collections');
        Schema::drop('projects');
    }

    public function down(): void
    {
        throw new RuntimeException('Esta migración elimina la agrupación por proyectos. Para recuperarla, restaura la copia previa a la migración.');
    }
};
