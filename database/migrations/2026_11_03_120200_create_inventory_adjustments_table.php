<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inventory_adjustments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('inventory_id')->constrained('inventories')->restrictOnDelete();
            $table->foreignId('variant_id')->constrained('variants')->restrictOnDelete();
            $table->integer('system_quantity');
            $table->integer('counted_quantity');
            $table->integer('difference')->comment('counted_quantity - system_quantity');
            $table->enum('status', ['pending', 'approved', 'applied', 'rejected'])->default('pending');
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('applied_at')->nullable();
            $table->text('comment')->nullable();
            $table->timestamps();

            $table->unique(['inventory_id', 'variant_id']);
            $table->index(['inventory_id', 'status']);
        });

        // difference > 0: entrada; difference < 0: salida; difference = 0: sin movimiento.
        // La aprobación y aplicación al kardex corresponden al servicio de ajustes.
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_adjustments');
    }
};
