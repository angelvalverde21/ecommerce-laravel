<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('inventory_receptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->constrained()->restrictOnDelete();
            $table->morphs('source');
            $table->uuid('request_id');
            $table->char('payload_hash', 64);
            $table->date('received_on');
            $table->text('notes')->nullable();
            $table->foreignId('received_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reversed_at')->nullable();
            $table->foreignId('reversed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('reversal_reason')->nullable();
            $table->timestamps();
            $table->unique(['store_id', 'request_id']);
        });
        Schema::create('inventory_reception_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('inventory_reception_id')->constrained()->restrictOnDelete();
            // Preserve the original identifiers and descriptions even if the catalog changes.
            $table->unsignedBigInteger('variant_id');
            $table->unsignedBigInteger('product_id');
            $table->string('product_name');
            $table->string('sku')->nullable();
            $table->json('options');
            $table->unsignedInteger('quantity');
            $table->string('comment', 100);
            $table->timestamps();
            $table->unique(['inventory_reception_id', 'variant_id'], 'reception_variant_unique');
        });
        Schema::table('kardexes', function (Blueprint $table) {
            $table->foreignId('inventory_reception_item_id')->nullable()->constrained()->restrictOnDelete();
            $table->unsignedBigInteger('reverses_kardex_id')->nullable()->unique();
            $table->foreign('reverses_kardex_id')->references('id')->on('kardexes')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('kardexes', function (Blueprint $table) {
            $table->dropForeign(['reverses_kardex_id']);
            $table->dropUnique(['reverses_kardex_id']);
            $table->dropColumn('reverses_kardex_id');
            $table->dropConstrainedForeignId('inventory_reception_item_id');
        });
        Schema::dropIfExists('inventory_reception_items');
        Schema::dropIfExists('inventory_receptions');
    }
};
