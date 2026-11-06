<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inventory_counts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('inventory_session_id')->constrained('inventory_sessions')->restrictOnDelete();
            $table->foreignId('location_id')->constrained('locations')->restrictOnDelete();
            $table->foreignId('variant_id')->constrained('variants')->restrictOnDelete();
            $table->foreignId('user_id')->constrained('users')->restrictOnDelete();
            $table->unsignedInteger('quantity');
            $table->unsignedInteger('count_round')->default(1);
            $table->enum('status', ['valid', 'replaced', 'cancelled'])->default('valid');
            $table->timestamp('counted_at');
            $table->timestamps();

            // Nombre explícito para respetar el límite de 64 caracteres de MySQL.
            $table->index(['inventory_session_id', 'location_id', 'variant_id'], 'inventory_counts_session_location_variant_index');
            $table->index(['variant_id', 'status']);
        });

        // Se permiten varios registros por variante, ubicación y ronda para acumular.
        // Un reconteo debe marcar los conteos anteriores como replaced; no sumarlos.
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_counts');
    }
};
