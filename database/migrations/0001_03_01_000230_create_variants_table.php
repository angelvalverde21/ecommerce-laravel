<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */

    /* ojo esta tabla es solo para saber el cardinal del producto cartesiano entre options y options_value */
    
    public function up(): void
    {
        Schema::create('variants', function (Blueprint $table) {

            $table->id();

            $table->foreignId('product_id')->constrained()->onDelete('cascade'); 
            $table->string('sku')->unique();
            $table->decimal('price', 8, 2)->nullable();
            $table->decimal('compare_at_price', 8, 2)->nullable();
            $table->decimal('weight', 8, 2)->nullable();
            $table->string('barcode')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('variants');
    }
};
