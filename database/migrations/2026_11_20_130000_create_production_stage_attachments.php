<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
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
    }
    public function down(): void { Schema::dropIfExists('production_stage_attachments'); }
};
