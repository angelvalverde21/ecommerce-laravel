<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('production_stage_progress', fn (Blueprint $table) => $table->boolean('reuses_mold')->default(false));
    }

    public function down(): void
    {
        Schema::table('production_stage_progress', fn (Blueprint $table) => $table->dropColumn('reuses_mold'));
    }
};
