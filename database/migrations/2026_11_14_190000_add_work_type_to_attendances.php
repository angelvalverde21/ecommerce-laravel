<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('attendances', 'work_type')) {
            Schema::table('attendances', fn (Blueprint $table) => $table->string('work_type', 20)->nullable());
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('attendances', 'work_type')) {
            Schema::table('attendances', fn (Blueprint $table) => $table->dropColumn('work_type'));
        }
    }
};
