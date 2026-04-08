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
        Schema::create('batches', function (Blueprint $table) {
            $table->id();

            $table->nullableMorphs('batchable'); // Polimórfica para asociar con diferentes modelos, como Manufacture, etc
            $table->string('name'); //nombre del lote por ejemplo polera doble capucha y dentro (inventory_variants) se colocan todas sus variantes
            $table->foreignId('user_id')
                ->constrained()
                ->cascadeOnDelete();
            $table->foreignId('store_id')->constrained()->cascadeOnDelete(); // Si se elimina la tienda, se eliminan las manufacturas asociadas

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('batches');
    }
};
