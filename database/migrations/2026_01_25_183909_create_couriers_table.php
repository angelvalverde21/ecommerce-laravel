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

            $table->string('name');
            $table->string('phone')->nullable();
            $table->tinyInteger('status')
                ->default(1)
                ->comment('1 = active, 0 = blocked');
            //El courier acepta pago contra entrega, osea que el pago se realiza al momento de la entrega
            $table->boolean('is_cod')->default(false); // COD significa Cash On Delivery (también visto como Collect On Delivery). el courier acepta pago contra entrega, por ejemplo shalom e indriver (pago en destino)
            $table->boolean('freight_collect')->default(false); //El courier acepta flete por cobrar, por ejemplo shalom auqnue indriver lo tiene
            $table->enum('delivery_type', ['agency', 'express', 'store_pickup'])->default('agency'); //El courier acepta envios express, por ejemplo indriver y shalom no lo tiene

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
