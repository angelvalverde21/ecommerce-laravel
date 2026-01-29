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
        Schema::create('option_values', function (Blueprint $table) {
            
            $table->id();
            $table->foreignId('option_id')->constrained()->cascadeOnDelete(); // Si se elimina la opción, se eliminan los valores asociados (sus option_values)
            $table->string('value');
            $table->integer('sort_order')->default(0);

            $table->unique(['option_id', 'value']);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('option_values');
    }
};
