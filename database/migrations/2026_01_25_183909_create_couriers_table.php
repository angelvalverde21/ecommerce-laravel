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
        Schema::create('couriers', function (Blueprint $table) {
            $table->id();

            $table->string('name');
            $table->string('email')->unique()->nullable();

            $table->tinyInteger('status')
                ->default(1)
                ->comment('1 = active, 0 = blocked');

            $table->foreignId('identity_id')->nullable()
                ->constrained()
                ->onDelete('cascade'); // DNI, RUC, CE, etc

            $table->string('document_number', 20)->nullable()->unique();
            // Campos del empleado
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('couriers');
    }
};
