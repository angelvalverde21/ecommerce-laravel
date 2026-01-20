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
            $table->foreignId('store_id')->nullable()->constrained()->onDelete('cascade');
            $table->foreignId('product_id')->constrained()->onDelete('cascade');
            //Parent option (auto referencia)
            $table->foreignId('parent_id')
                ->nullable()
                ->constrained('options')
                ->nullOnDelete();

            $table->boolean('multiple')->default(false); //este campo indica si la variable entra al producto cartesiano, true indica que es multiple por ejemplo tallas tiene multples varlores, colores tambien
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
