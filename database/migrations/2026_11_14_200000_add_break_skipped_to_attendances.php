<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('attendances', 'break_skipped')) {
            Schema::table('attendances', fn (Blueprint $table) => $table->boolean('break_skipped')->default(false));
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('attendances', 'break_skipped')) {
            Schema::table('attendances', fn (Blueprint $table) => $table->dropColumn('break_skipped'));
        }
    }
};
