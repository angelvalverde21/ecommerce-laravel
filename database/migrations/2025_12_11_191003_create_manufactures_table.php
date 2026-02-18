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
        Schema::create('manufactures', function (Blueprint $table) {
            $table->id();
            $table->string('name'); //nombre del proyecto de manufactura, no es del producto
            $table->float('budget')->nullable()->default('0.00'); //presupuesto asignado al proyecto de manufatura
            // $table->integer('total')->default(0);
            $table->integer('quantity_total')->default(0); // Total producidos
            $table->integer('quantity_failures')->default(0); // Merma o malogrados
            $table->enum('type', ['order', 'production'])->default('order'); // Para indicar si es una orden de compra para manufactura (order) o una orden de producción interna (production)    
            $table->decimal('cost', 10, 2)->default(0.00); // Costo de producción calculado entre todo lo gastado y dividido entre lo producido
            $table->foreignId('supplier_id')->nullable()->constrained()->nullOnDelete(); //En caso sea una orden de compra para manufactura, se asigna el proveedor, en caso contrario se deja nulo y se asume que es una orden de producción interna (donde se tendra purchases y cada purchase es tiene un proveedor)
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('store_id')->nullable()->constrained()->cascadeOnDelete(); // Si se elimina la tienda, se eliminan las manufacturas asociadas
            $table->dateTime('manufacture_end')->nullable(); //fecha de fin de manufactura
            $table->dateTime('manufacture_start')->nullable(); //fecha de inicio de manufactura
            // $table->foreignId('section_id')->nullable()->constrained('sections')->cascadeOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('manufactures');
    }
};
