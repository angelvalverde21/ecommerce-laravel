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
        Schema::create('colors', function (Blueprint $table) {
            $table->id();

            $table->foreignId('product_id')->constrained()->onDelete('cascade');
            $table->string('name'); // ej. "Rojo intenso", "Rojo brillante"
            $table->string('code')->nullable(); // referencia a partida o lote
            $table->string('partida')->nullable(); // referencia a partida o lote
            $table->text('description')->nullable(); // detalles extra si quieres

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('colors');
    }
};
