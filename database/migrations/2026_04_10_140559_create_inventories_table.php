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
        Schema::create('inventories', function (Blueprint $table) {
            $table->id();
            $table->enum('status', [
                'draft',
                'in_progress',
                'completed',
                'cancelled'
            ])->default('draft');
            $table->nullableMorphs('inventoriable'); // Polimórfica para asociar con diferentes modelos, como Manufacture, etc
            $table->string('name'); //nombre del lote por ejemplo polera doble capucha y dentro (inventory_variants) se colocan todas sus variantes
            $table->foreignId('created_user_id')
                ->constrained('users')
                ->cascadeOnDelete()->comment('el created_user_id es el que crea el inventario, no necesariamente el que lo realiza');
            $table->foreignId('store_id')->constrained()->cascadeOnDelete(); // Si se elimina la tienda, se eliminan las manufacturas asociadas
            $table->text('comment')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();

            $table->index(['store_id', 'status']);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inventories');
    }
};
