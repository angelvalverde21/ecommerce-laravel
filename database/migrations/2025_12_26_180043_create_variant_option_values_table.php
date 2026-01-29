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
        Schema::create('variant_option_values', function (Blueprint $table) {
            // $table->id();
            // $table->foreignId('variant_id')->constrained()->cascadeOnDelete();
            // $table->foreignId('option_id')->nullable()->constrained()->cascadeOnDelete();

            // $table->timestamps();

            $table->id();

            //Relación con la variante (unidad vendible)
            $table->foreignId('variant_id')
                ->constrained()
                ->cascadeOnDelete(); // Si se elimina la variante, se eliminan los valores de opciones asociados

            //Relación con la opción (Color, Talla, Modelo, etc.)
            $table->foreignId('option_id')
                ->constrained()
                ->cascadeOnDelete();

            //Relación con el valor de la opción (Negro, M, X3, etc.)
            $table->unsignedBigInteger('option_value_id');
            $table->foreign('option_value_id')->references('id')->on('option_values')->cascadeOnDelete();

            //Reglas de integridad
            // Una variante solo puede tener UN valor por opción
            $table->unique(['variant_id', 'option_id'], 'u_variant_option');
            
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('variant_option_values');
    }
};
