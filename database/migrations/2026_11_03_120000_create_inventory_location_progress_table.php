<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inventory_location_progress', function (Blueprint $table) {
            $table->id();
            $table->foreignId('inventory_id')->constrained('inventories')->restrictOnDelete();
            $table->foreignId('location_id')->constrained('locations')->restrictOnDelete();
            $table->foreignId('verified_by')->nullable()->constrained('users')->nullOnDelete();
            $table->enum('status', ['pending', 'in_progress', 'counted', 'verified'])->default('pending');
            $table->timestamp('started_at')->nullable();
            $table->timestamp('counted_at')->nullable();
            $table->timestamp('verified_at')->nullable();
            $table->timestamps();

            $table->unique(['inventory_id', 'location_id']);
            $table->index(['inventory_id', 'status']);
        });

        // Registrar solo ubicaciones finales inventariables. El servicio debe evitar
        // incluir simultáneamente una ubicación padre y cualquiera de sus hijas.
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_location_progress');
    }
};
