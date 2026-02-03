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
        Schema::create('options', function (Blueprint $table) {

            $table->id();
            $table->foreignId('store_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            //Parent option (auto referencia)
            $table->foreignId('parent_id')
                ->nullable()
                ->constrained('options')
                ->cascadeOnDelete(); //
            $table->string('name');
            $table->string('label');
            $table->integer('sort_order')->default(0);
            $table->timestamps();

            $table->unique(['store_id', 'product_id', 'name']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('options');
    }
};
