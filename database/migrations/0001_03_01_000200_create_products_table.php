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
            $table->string('tags')->nullable();
            $table->string('slug');
            $table->text('body')->nullable();

            $table->decimal('price', 8, 2)->nullable();

            $table->foreignId('user_id')->nullable()->constrained()->onDelete('cascade'); //cascade quiere decir que elimina a los hijos y no al revez
            //$table->foreignId('owner_id')->nullable()->constrained('users') // aquí le dices a qué tabla apunta
            //->onDelete('cascade');

            $table->foreignId('store_id')->nullable()->constrained()->onDelete('cascade');
            $table->foreignId('category_id')->nullable()->constrained()->onDelete('cascade');

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
