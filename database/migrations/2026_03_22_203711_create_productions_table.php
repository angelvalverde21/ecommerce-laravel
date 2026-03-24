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
        Schema::create('productions', function (Blueprint $table) {
            $table->id();
            $table->string('name'); //nombre del proyecto, no es del producto
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();  //Responsable de la produccion
            $table->foreignId('store_id')->nullable()->constrained()->cascadeOnDelete(); // Si se elimina la tienda, se eliminan las producciones asociadas
            $table->dateTime('production_end')->nullable(); //fecha de fin de manufactura
            $table->dateTime('production_start')->nullable(); //fecha de inicio de manufactura
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('productions');
    }
};
