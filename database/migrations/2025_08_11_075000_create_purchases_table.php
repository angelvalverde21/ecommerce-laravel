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
        Schema::create('purchases', function (Blueprint $table) {
            
            $table->id();
            $table->text('observations')->nullable();
            $table->foreignId('supplier_id')->nullable()->constrained()->nullOnDelete(); // Si se elimina el proveedor, deja el campo en null
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete(); // Si se elimina el usuario, deja el campo en null
            $table->foreignId('store_id')->constrained()->cascadeOnDelete(); // Si se elimina la tienda, se eliminan las compras asociadas
            $table->dateTime('purchase_end')->nullable(); //fecha de fin de
            $table->dateTime('purchase_start')->nullable(); //fecha de inicio de
            
            $table->morphs('purchaseable'); //ya agrega el index

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {   
        Schema::dropIfExists('purchases');
    }
};
