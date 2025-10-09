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
        Schema::create('variants', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('product_id');
            $table->string('title')->nullable();
            $table->decimal('price', 10, 2)->default(0);
            $table->integer('position')->default(0);
            $table->string('inventory_policy')->nullable();
            $table->decimal('compare_at_price', 10, 2)->nullable();
            $table->string('option1')->nullable();
            $table->string('option2')->nullable();
            $table->string('option3')->nullable();
            $table->boolean('taxable')->default(false);
            $table->string('barcode')->nullable();
            $table->string('fulfillment_service')->nullable();
            $table->integer('grams')->default(0);
            $table->string('inventory_management')->nullable();
            $table->boolean('requires_shipping')->default(true);
            $table->string('sku')->nullable();
            $table->decimal('weight', 10, 2)->default(0);
            $table->string('weight_unit')->default('kg');
            $table->unsignedBigInteger('inventory_item_id')->nullable();
            $table->integer('inventory_quantity')->default(0);
            $table->integer('old_inventory_quantity')->default(0);
            $table->string('admin_graphql_api_id')->nullable();
            $table->unsignedBigInteger('image_id')->nullable();

            // $table->foreign('product_id')->references('id')->on('products')->cascadeOnDelete();

            $table->foreignId('product_id')->nullable()->constrained()->onDelete('cascade');

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
