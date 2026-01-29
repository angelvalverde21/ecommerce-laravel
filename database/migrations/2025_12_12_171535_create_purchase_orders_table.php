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
        Schema::create('purchase_orders', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('observations')->nullable();
            $table->foreignId('supplier_id')->nullable()->constrained()->nullOnDelete(); // Si se elimina el proveedor, se deja en null
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete(); //creador de la orden
            $table->foreignId('store_id')->nullable()->constrained()->cascadeOnDelete(); // Si se elimina la tienda, se eliminan las ordenes de compra asociadas
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('purchase_orders');
    }
};
