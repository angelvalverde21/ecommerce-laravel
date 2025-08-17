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
        Schema::create('purchases', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('observations')->nullable();
            $table->decimal('quantity', 8, 2)->nullable();
            $table->string('unit_id');
            $table->decimal('price', 8, 2)->nullable();
            $table->decimal('total', 8, 2)->nullable();
            $table->foreignId('supplier_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('store_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('section_id')->nullable()->constrained()->onDelete('set null');
            $table->morphs('purchaseable'); //ya agrega el index

            // Nueva con purchases_type se hace asi porque la tabla es distinta no sigue la convencion para usar foreignId
    
            // $table->unsignedBigInteger('purchase_type_id');
            // $table->foreign('purchase_type_id')->references('id')->on('purchase_type'); 

            //-------
            // $table->foreignId('store_id')->nullable()->constrained()->onDelete('set null');

            // $table->index(['purchaseable_type', 'purchaseable_id']);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {   
        Schema::dropIfExists('purchases');
    }
};
