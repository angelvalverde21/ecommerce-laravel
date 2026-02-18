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
        Schema::create('kardexes', function (Blueprint $table) {

            $table->id();

            $table->foreignId('product_id')->nullable()->constrained()->nullOnDelete('cascade');
            $table->foreignId('variant_id')->nullable()->constrained()->nullOnDelete('cascade');
            $table->morphs('kardexable'); // kardexable_id + kardexable_type

            $table->integer('quantity')->default(0);

            $table->enum('direction', ['in', 'out'])->default('in');
            $table->string('comment')->nullable();


            $table->index('product_id');
            $table->index('variant_id');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('kardexes');
    }
};
