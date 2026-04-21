<?php

use App\Models\Product;
use App\Models\Status;
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
        Schema::create('products', function (Blueprint $table) {

            $table->id();


            $table->string('name')->nullable();
            $table->string('slug')->nullable();
            $table->string('tags')->nullable();

            $table->text('body')->nullable();

            $table->string('online_store_url')->nullable();

            $table->foreignId('category_id')->nullable()->constrained()->nullOnDelete(); //si se borra las categorias deja el campo en null
            $table->string('barcode')->nullable();

            // Si se elimina el usuario, la base de datos elimina automáticamente todos los registros de products asociados a ese usuario
            $table->foreignId('user_id')
                ->constrained()
                ->cascadeOnDelete();

            // Si se elimina la tienda, la base de datos elimina automáticamente
            // todos los registros de products asociados a esa tienda
            $table->foreignId('store_id')
                ->constrained()
                ->cascadeOnDelete();

            //==========================================================================
            $table->index('name'); //para las busquedas mas rapidas
            $table->unique(['slug', 'store_id', 'status']);

            //======================== Campos comunes ===================================

            $table->float('price')->nullable()->default('0.00');
            $table->float('compare_at_price')->nullable()->default('0.00');
            $table->unsignedInteger('quantity')->default(0);

            $table->string('origin')->nullable();
            $table->tinyInteger('status')->default(Status::ACTIVE)->comment('Product::TRASH = 0, Product::ACTIVE = 1, Product::DRAFT = 2, Product::ARCHIVED = 3'); //desde -128 a 127
            $table->unsignedTinyInteger('sort_order')->default(0); //del 1 al 255
            $table->string('sku')->nullable()->unique();
            //=====================================================================

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};


