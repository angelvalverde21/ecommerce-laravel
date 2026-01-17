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
        Schema::create('images', function (Blueprint $table) {

            $table->id();

            $table->string('name')->nullable(); //guarda el nombre original del archivo
            $table->string('title')->nullable();// titulo de la imagen (opcional)
            $table->string('thumbnail')->nullable();
            $table->string('medium')->nullable();
            $table->string('large')->nullable();

            $table->morphs('imageable');

            $table->timestamps();
            
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('images');
    }
};
