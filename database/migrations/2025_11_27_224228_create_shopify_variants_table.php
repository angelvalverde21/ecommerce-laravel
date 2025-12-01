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
        Schema::create('shopify_variants', function (Blueprint $table) {

            $table->id(); //shopify_variant_id

            $table->unsignedBigInteger('shopify_product_id'); //este campo hace referencia al id de la tabla shopify_products, no al campo shopify_product_id de Shopify
            $table->foreign('shopify_product_id')->references('id')->on('shopify_products')->onDelete('cascade');
 
            $table->string('shopify_variant_id');
            $table->string('title');
            $table->string('sku')->nullable();
            $table->decimal('price', 10, 2)->nullable();
            $table->decimal('price_wholesaler', 10, 2)->nullable();
            $table->decimal('price_live', 10, 2)->nullable();
            $table->decimal('price_blackfriday', 10, 2)->nullable();
            $table->decimal('price_feria', 10, 2)->nullable();
            $table->integer('quantity')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('shopify_variants');
    }
};
