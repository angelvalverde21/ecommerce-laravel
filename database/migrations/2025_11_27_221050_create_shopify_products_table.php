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
        Schema::create('shopify_products', function (Blueprint $table) {

            $table->id(); //shopify_product_id
            $table->string('shopify_product_id');
            $table->string('title');
            $table->string('image')->nullable();
            $table->string('status')->nullable();
            $table->string('online_store_url')->nullable();
            $table->boolean('sync_status')->default(true);
            // $table->text('body_html')->nullable();
            $table->timestamp('created_at_shopify')->nullable();
            $table->timestamp('updated_at_shopify')->nullable();
        
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('shopify_products');
    }
};
