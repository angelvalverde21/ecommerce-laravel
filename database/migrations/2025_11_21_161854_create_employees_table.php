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
        Schema::create('employees', function (Blueprint $table) {

            $table->id();

            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete(); // Si se elimina el usuario, se eliminan los empleados asociados (la relacion 1 a 1)

            // Campos del empleado
            $table->decimal('salary', 10, 2)->nullable();
            $table->tinyInteger('comission')->nullable();
            // $table->date('date_entry')->nullable();
            $table->string('tag_search')->nullable(); //telefono de trabajo (del empleado)
            $table->string('phone')->nullable(); //telefono de trabajo (del empleado)
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('employees');
    }
};
