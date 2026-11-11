<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('production_stages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('manufacture_id')->constrained('manufactures')->cascadeOnDelete();
            $table->string('code', 40);
            $table->string('name');
            $table->unsignedTinyInteger('position');
            $table->string('status', 25)->default('pending');
            $table->string('responsible')->nullable();
            $table->date('expected_end')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->text('notes')->nullable();
            $table->unsignedInteger('sample_quantity')->nullable();
            $table->text('sample_details')->nullable();
            $table->json('sample_photos')->nullable();
            $table->string('sample_review', 25)->default('pending');
            $table->text('review_notes')->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->unsignedInteger('revision')->default(0);
            $table->timestamps();
            $table->unique(['manufacture_id', 'code']);
        });

        Schema::create('production_stage_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('production_stage_id')->constrained('production_stages')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->json('changes');
            $table->timestamps();
        });

        Schema::table('purchases', function (Blueprint $table) {
            $table->foreignId('production_stage_id')->nullable()->after('id')->constrained('production_stages')->restrictOnDelete();
        });

        DB::table('manufactures')->where('type', 'production')->orderBy('id')->chunkById(100, function ($productions) {
            foreach ($productions as $production) {
                foreach (\App\Models\ProductionStage::DEFINITIONS as $position => $definition) {
                    DB::table('production_stages')->insert([
                        'manufacture_id' => $production->id,
                        'code' => $definition[0],
                        'name' => $definition[1],
                        'position' => $position,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }
        });
    }

    public function down(): void
    {
        Schema::table('purchases', fn (Blueprint $table) => $table->dropConstrainedForeignId('production_stage_id'));
        Schema::dropIfExists('production_stage_events');
        Schema::dropIfExists('production_stages');
    }
};
