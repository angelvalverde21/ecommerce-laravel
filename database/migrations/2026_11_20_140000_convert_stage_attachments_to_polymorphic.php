<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    private const OWNER = 'production_stage_progress';

    public function up(): void
    {
        Schema::create('attachments', function (Blueprint $table) {
            $table->id();
            $table->morphs('attachable');
            $table->string('name');
            $table->string('path');
            $table->string('mime_type')->nullable();
            $table->unsignedBigInteger('size');
            $table->unsignedBigInteger('uploaded_by')->nullable();
            $table->timestamps();
        });
        try {
            DB::table('production_stage_attachments')->orderBy('id')->chunkById(200, function ($rows) {
                DB::table('attachments')->insert($rows->map(function ($row) {
                    $data = (array) $row;
                    $data['attachable_type'] = self::OWNER;
                    $data['attachable_id'] = $data['progress_id'];
                    unset($data['progress_id']);
                    return $data;
                })->all());
            });
            if (DB::table('attachments')->count() !== DB::table('production_stage_attachments')->count()) {
                throw new RuntimeException('No se pudieron verificar todos los adjuntos migrados.');
            }
        } catch (Throwable $error) {
            Schema::drop('attachments');
            throw $error;
        }
        Schema::drop('production_stage_attachments');
    }

    public function down(): void
    {
        if (DB::table('attachments')->where('attachable_type', '!=', self::OWNER)->exists()) {
            throw new RuntimeException('No se puede revertir: existen adjuntos de otros módulos.');
        }
        Schema::create('production_stage_attachments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('progress_id')->constrained('production_stage_progress')->cascadeOnDelete();
            $table->string('name');
            $table->string('path');
            $table->string('mime_type')->nullable();
            $table->unsignedBigInteger('size');
            $table->unsignedBigInteger('uploaded_by')->nullable();
            $table->timestamps();
        });
        try {
            DB::table('attachments')->orderBy('id')->chunkById(200, function ($rows) {
                DB::table('production_stage_attachments')->insert($rows->map(function ($row) {
                    $data = (array) $row;
                    $data['progress_id'] = $data['attachable_id'];
                    unset($data['attachable_id'], $data['attachable_type']);
                    return $data;
                })->all());
            });
        } catch (Throwable $error) {
            Schema::drop('production_stage_attachments');
            throw $error;
        }
        Schema::drop('attachments');
    }
};
