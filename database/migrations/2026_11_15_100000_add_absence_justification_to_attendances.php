<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('attendances', function (Blueprint $table) {
            $table->boolean('absence_justified')->default(false);
            $table->string('justification_reason', 1000)->nullable();
            $table->unsignedBigInteger('justified_by')->nullable();
            $table->timestamp('justified_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('attendances', fn (Blueprint $table) => $table->dropColumn(['absence_justified', 'justification_reason', 'justified_by', 'justified_at']));
    }
};
