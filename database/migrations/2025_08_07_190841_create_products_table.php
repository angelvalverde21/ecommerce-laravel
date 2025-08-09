<?php

use App\Models\Product;
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

            $table->tinyInteger('status')->default(Product::BORRADOR)->comment('Product::PUBLICADO = 1, Product::BORRADOR = 3, Product::ELIMINADO = 0, Product::ARCHIVADO = 2]'); //desde -128 a 127
            
            $table->string('name')->nullable();
            $table->string('slug');
            $table->string('sku');
            $table->string('barcode');
            $table->text('body')->nullable();
            $table->string('extract')->nullable();

            $table->float('price')->nullable()->default('0.00');
  

            $table->foreignId('user_id')->nullable()->constrained()->onDelete('cascade');
            $table->foreignId('unit_id')->nullable()->constrained()->onDelete('set null'); //Si borras una unidad, el campo unit_id en productos relacionados se pone a NULL
            $table->foreignId('store_id')->nullable()->constrained()->onDelete('cascade');

            $table->index('name'); //para las busquedas mas rapidas

            $table->unique(['slug', 'store_id']);

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
