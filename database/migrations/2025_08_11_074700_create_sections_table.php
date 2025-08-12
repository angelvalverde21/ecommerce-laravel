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
        Schema::create('sections', function (Blueprint $table) {

            $table->id();

            $table->foreignId('store_id')->constrained()->cascadeOnDelete();

            // Referencia al padre (nullable, porque las secciones raíz no tienen padre)
            $table->foreignId('parent_id')->nullable()->constrained('sections')->cascadeOnDelete();

            $table->string('name');
            $table->string('slug');

            $table->unsignedInteger('order')->default(0);

            $table->timestamps();

            // Índice único para que el slug no se repita entre hermanos dentro de la misma tienda
            $table->unique(['store_id', 'parent_id', 'slug']);
            
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sections');
    }
};
