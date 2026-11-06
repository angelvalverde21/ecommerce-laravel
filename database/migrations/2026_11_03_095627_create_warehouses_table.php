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
        Schema::create('warehouses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->constrained()->restrictOnDelete();

            $table->string('name'); //nombre descriptivo del almacén, por ejemplo: Almacén Lima, Almacén Arequipa, etc
            $table->string('code', 50); //codigo unico del almacén, por ejemplo ALM-01, ALM-02, etc

            /*
                main	Almacén principal	Almacén principal de la tienda A
                branch	Almacén de una sucursal	Almacén de la sucursal Los Olivos
                central	Centro de distribución	Almacén que abastece varias sucursales
                returns	Almacén de devoluciones	Mercadería devuelta pendiente de revisión
                other	Otros almacenes	Almacén temporal
            */

            $table->foreignId('branch_id')
                ->constrained()
                ->restrictOnDelete();

            $table->string('address')->nullable();

            $table->boolean('is_active')->default(true);

            $table->timestamps();

            $table->unique(['store_id', 'code']);
            $table->index(['branch_id', 'is_active']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('warehouses');
    }
};
