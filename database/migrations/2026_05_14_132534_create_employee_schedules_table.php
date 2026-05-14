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
        Schema::create('employee_schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->constrained()->cascadeOnDelete();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            //Tipo de trabajo home office o onsite
            $table->enum('work_type', ['home_office', 'onsite'])->default('onsite');

            $table->tinyInteger('day_of_week')->unsigned()->nullable(); //
            $table->time('start_time')->default('09:00:00')->nullable();
            $table->time('end_time')->default('19:00:00');

            //Dias de la semana

            //PERMITIR HORAS EXTRAS
            $table->boolean('allow_extra_hours')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('employee_schedules');
    }
};
