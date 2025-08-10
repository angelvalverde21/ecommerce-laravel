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
        Schema::create('suppliers', function (Blueprint $table) {
            $table->id();

            $table->string('name');
            $table->string('address')->nullable();
            $table->string('email')->nullable()->unique();
            $table->foreignId('store_id')->nullable()->constrained()->onDelete('cascade');
            $table->unsignedBigInteger('phone')->unique()->nullable();
            $table->foreignId('identity_id')->nullable()->constrained()->onDelete('cascade'); //DNI, RUC, CE, etc
            $table->string('document_number', 20)->nullable()->unique();

            $table->unique(['store_id', 'phone']);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('suppliers');
    }
};
