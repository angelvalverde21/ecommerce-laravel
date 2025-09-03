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
        Schema::create('variant_option_value', function (Blueprint $table) {
            // $table->id();
            $table->foreignId('variant_id')->constrained()->onDelete('cascade');
            $table->foreignId('option_value_id')->constrained('option_value')->onDelete('cascade');
            $table->primary(['variant_id', 'option_value_id']);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('variant_option_value');
    }
};
