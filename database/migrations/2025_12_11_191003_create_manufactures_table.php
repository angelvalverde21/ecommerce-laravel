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
        Schema::create('manufactures', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            // $table->integer('total')->default(0);
            $table->integer('quantity_total')->default(0); // Total producidos
            $table->integer('quantity_failures')->default(0); // Merma o malogrados
            $table->decimal('cost', 10, 2)->default(0.00); // Costo de producción
            $table->foreignId('store_id')->nullable()->constrained()->onDelete('cascade');
            // $table->foreignId('section_id')->nullable()->constrained('sections')->cascadeOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('manufactures');
    }
};
