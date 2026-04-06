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
        Schema::create('manufacture_variant', function (Blueprint $table) {
            $table->id();
            $table->foreignId('manufacture_id')->constrained()->onDelete('cascade');
            $table->foreignId('variant_id')->constrained()->onDelete('cascade');
            $table->integer('quantity_init')->default(0); //stock inicial de manufacture_variant, se usara para cuando se cree la orden de compra, sera el stock maximo, por ejemplo el pedido que se le hace al proveedor
            $table->integer('stock')->default(0); //sera el stock que se ira avanzando, un stock parcial que considera la variante y el manufacture_id, osea que se ira actualizando segun se vaya vendiendo los productos
            $table->decimal('price', 10, 2)->default(0.00);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('manufacture_variant');
    }
};
