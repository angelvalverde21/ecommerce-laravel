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
        Schema::create('prices', function (Blueprint $table) {
            $table->id();
            $table->tinyInteger('status')->default(Status::ACTIVE)->comment('Product::TRASH = 0, Product::ACTIVE = 1, Product::DRAFT = 2, Product::ARCHIVED = 3'); //desde -128 a 127
            $table->string('name');
            $table->string('value'); //precio que el cliente pagara
            $table->string('currency', 3)->default('PEN');
            $table->morphs('priceable');
            $table->unique(['priceable_type', 'priceable_id', 'name']);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('prices');
    }
};
