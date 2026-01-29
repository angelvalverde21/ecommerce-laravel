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

            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete(); // Si se elimina el usuario, se eliminan los suppliers asociados
            // $table->string('name');
            // $table->tinyInteger('status')
            //     ->default(1)
            //     ->comment('1 = active, 0 = blocked');
            // $table->string('address')->nullable();
            // $table->string('email')->nullable()->unique();
            // $table->foreignId('store_id')->nullable()->constrained()->cascadeOnDelete();
            // $table->unsignedBigInteger('phone')->unique()->nullable();
            // $table->foreignId('identity_id')->nullable()->constrained()->cascadeOnDelete(); //DNI, RUC, CE, etc
            // $table->string('document_number', 20)->nullable()->unique();

            // $table->unique(['store_id', 'phone']);

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
