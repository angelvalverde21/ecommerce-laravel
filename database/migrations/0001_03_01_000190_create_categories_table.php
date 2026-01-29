<?php

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
        Schema::create('categories', function (Blueprint $table) {
            $table->id();

            $table->string('name');
            $table->string('full_name')->nullable();
            $table->string('slug');
            $table->foreignId('parent_id')->nullable()->constrained('categories')->cascadeOnDelete();

            // $table->boolean('is_color')->default(true); //Se elimina porque ahora el producto sera el color, y el modelo collection agrupara a los colores
            // $table->boolean('is_size')->default(false); //Entonces ahora solo sera necesario consultar si la categoria tiene tallas

            $table->foreignId('user_id')->constrained()->cascadeOnDelete(); //si se borra el usuario deja el campo en null
            $table->foreignId('store_id')->constrained()->cascadeOnDelete(); //si se borra la tienda deja el campo en null



            //======================== Campos comunes ===================================

            $table->string('origin')->nullable();
            $table->tinyInteger('status')->default(Status::ACTIVE)->comment('Product::TRASH = 0, Product::ACTIVE = 1, Product::DRAFT = 2, Product::ARCHIVED = 3'); //desde -128 a 127
            $table->unsignedTinyInteger('sort_order')->default(0); //del 1 al 255

            //===========================================================================
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('categories');
    }
};
