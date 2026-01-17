<?php

use App\Models\Status;
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
        Schema::create('sizes', function (Blueprint $table) {

            $table->id();


            $table->string('name');
            $table->foreignId('product_id')->nullable()->constrained()->onDelete('cascade');
            $table->unique(['name', 'product_id']);


            //======================== Campos comunes ===================================
            
            $table->float('price')->nullable()->default('0.00');
            $table->float('compare_at_price')->nullable()->default('0.00');
            $table->unsignedInteger('quantity')->default(0);

            $table->string('origin')->nullable();
            $table->tinyInteger('status')->default(Status::ACTIVE)->comment('Product::TRASH = 0, Product::ACTIVE = 1, Product::DRAFT = 2, Product::ARCHIVED = 3'); //desde -128 a 127
            $table->unsignedTinyInteger('sort_order')->default(0); //del 1 al 255

            $table->string('sku')->nullable()->unique();
            //=====================================================================

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sizes');
    }
};
