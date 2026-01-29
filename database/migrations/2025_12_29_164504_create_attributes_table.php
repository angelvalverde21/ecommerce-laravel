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
        Schema::create('attributes', function (Blueprint $table) {

            $table->id();
            $table->foreignId('store_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('value');
            $table->integer('sort_order')->default(0);
            $table->morphs('attributeable');
            // $table->unique(['attributeable_type', 'attributeable_id', 'store_id','name', 'value']);
            //Aqui definimos la clave unica con un nombre especifico para evitar que laravel le asigne uno muy largo que puede causar problemas en algunas bases de datos
            $table->unique(
                ['attributeable_type', 'attributeable_id', 'store_id', 'name', 'value'],
                'attr_attr_store_name_value_unique'
            );
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('attributes');
    }
};
