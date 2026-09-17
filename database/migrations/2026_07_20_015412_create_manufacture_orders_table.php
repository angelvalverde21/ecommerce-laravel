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
        Schema::create('manufacture_orders', function (Blueprint $table) {
            $table->id();
            $table->string('name'); //nombre del proyecto de manufactura, no es del producto
            $table->foreignId('supplier_id')->nullable()->constrained()->nullOnDelete(); //En caso sea una orden de compra para manufactura, se asigna el proveedor, en caso contrario se deja nulo y se asume que es una orden de producción interna (donde se tendra purchases y cada purchase es tiene un proveedor)
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete(); //Propietario o responsable del proyecto de manufactura, en caso se elimine el usuario, se deja nulo
            $table->foreignId('store_id')->nullable()->constrained()->cascadeOnDelete(); // Si se elimina la tienda, se eliminan las manufacturas asociadas
            $table->dateTime('date_end')->nullable(); //fecha de fin de manufactura
            $table->dateTime('date_start')->nullable(); //fecha de inicio de manufactura
            // $table->foreignId('section_id')->nullable()->constrained('sections')->cascadeOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('manufacture_orders');
    }
};
