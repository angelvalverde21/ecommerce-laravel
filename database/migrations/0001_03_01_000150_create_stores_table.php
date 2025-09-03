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
        Schema::create('stores', function (Blueprint $table) {
            $table->id();

            $table->string('name');
            $table->unsignedBigInteger('phone')->unique()->nullable();
            $table->string('email')->unique();
            $table->string('slug')->unique();
            $table->foreignId('identity_id')->nullable()->constrained()->onDelete('cascade');
            $table->string('document_number', 20)->nullable()->unique();

            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stores');
    }
};
