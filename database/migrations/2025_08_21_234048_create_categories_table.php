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
        Schema::create('categories', function (Blueprint $table) {
            $table->id();

            $table->string('name');
            $table->string('slug');
            $table->foreignId('parent_id')->nullable()->constrained('categories')->onDelete('cascade');
            
            $table->unsignedSmallInteger('sort_order')->default(0);

            $table->boolean('status')->default(true)->comment('1 activo 0 archivado'); //desde -128 a 127

            $table->boolean('is_color')->default(true);
            $table->boolean('is_size')->default(false);

            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('store_id')->constrained()->onDelete('cascade');


            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('categories');
    }
};
