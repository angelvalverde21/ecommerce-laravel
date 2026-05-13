<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('schedule_rules', function (Blueprint $table) {

            $table->id();
            $table->foreignId('schedule_id')->constrained()->cascadeOnDelete();

            $table->tinyInteger('day_of_week'); // 0=domingo, 6=sábado

            $table->time('check_in')->nullable();
            $table->time('check_out')->nullable();

            $table->boolean('is_rest_day')->default(false); //rest = descanzo
            $table->enum('work_mode', ['onsite', 'home_office'])->default('onsite');

            $table->timestamps();

            $table->unique(['schedule_id', 'day_of_week']);

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('schedule_rules');
    }
};
