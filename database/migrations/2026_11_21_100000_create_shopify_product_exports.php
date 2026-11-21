<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('shopify_product_exports', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignId('store_id')->constrained();
            $table->foreignId('product_id')->constrained();
            $table->string('shop_domain', 120);
            $table->string('handle', 120)->unique();
            $table->string('status', 25)->default('pending');
            $table->string('remote_product_id', 100)->nullable();
            $table->string('remote_status', 25)->nullable();
            $table->json('variant_links')->nullable();
            $table->string('payload_hash', 64)->nullable();
            $table->text('last_error')->nullable();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->timestamp('attempted_at')->nullable();
            $table->timestamp('synced_at')->nullable();
            $table->timestamps();
            $table->unique(['product_id', 'shop_domain'], 'shopify_exports_product_shop_unique');
            $table->unique(['shop_domain', 'remote_product_id'], 'shopify_exports_remote_unique');
        });
    }
    public function down(): void { Schema::dropIfExists('shopify_product_exports'); }
};
