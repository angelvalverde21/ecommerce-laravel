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
        Schema::create('couriers', function (Blueprint $table) {
            
            $table->id();

            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete(); // Si se elimina el usuario, se eliminan los couriers asociados

            //El courier acepta pago contra entrega, osea que el pago se realiza al momento de la entrega
            $table->boolean('is_cash_on_delivery')->default(false); 
            $table->boolean('is_freight_collect')->default(false); //El courier acepta flete por cobrar, por ejemplo shalom e indriver
            $table->boolean('is_express_shipping')->default(false); //El courier acepta envios express (rapidos)

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('couriers');
    }
};
