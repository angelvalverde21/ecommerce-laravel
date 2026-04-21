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

            $table->foreignId('store_id')
                ->nullable()
                ->constrained()
                ->nullOnDelete();

            $table->foreignId('product_id')
                ->nullable()
                ->constrained()
                ->nullOnDelete();

            $table->foreignId('variant_id')
                ->nullable()
                ->constrained()
                ->nullOnDelete();


            // $table->foreignId('manufacture_variant_id')
            //     ->default(150101)
            //     ->constrained('manufacture_variant');

            // $table->unsignedBigInteger('manufacture_variant_id');

            // $table->foreign('manufacture_variant_id', 'fk_kdx_manvar')
            //     ->references('id')
            //     ->on('manufacture_variant');

            $table->morphs('kardexable');

            $table->integer('quantity')->default(0);

            $table->enum('direction', ['in', 'out'])->default('in');
            $table->string('comment')->nullable();

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
