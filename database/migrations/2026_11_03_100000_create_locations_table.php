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
        Schema::create('locations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->constrained()->restrictOnDelete();

            // Relación con store (empresa)
            $table->foreignId('warehouse_id')
                ->constrained()
                ->cascadeOnDelete();

            // Jerarquía (self reference)
            $table->foreignId('parent_id')
                ->nullable()
                ->constrained('locations')
                ->nullOnDelete();

            // Datos básicos
            $table->string('name'); //nombre descriptivo de la ubicación, por ejemplo: Estante A, Estante B, etc
            $table->string('code')->nullable(); // RACK01 

            // Control
            $table->boolean('is_active')->default(true);

            $table->timestamps();

            // Índices útiles
            $table->index(['store_id']);
            $table->index(['parent_id']);
        });
    }
    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('locations');
    }
};
