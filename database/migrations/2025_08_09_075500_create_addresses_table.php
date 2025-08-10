<?php

use App\Models\Address;
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
        Schema::create('addresses', function (Blueprint $table) {

            $table->id();

            // $table->string('name');
            // $table->foreignId('identity_id')->nullable()->constrained()->onDelete('cascade'); //DNI, RUC, CE, etc
            // $table->tinyInteger('document_number');

            $table->string('type')->nullable(); //casa, oficina, trabajo, etc
            $table->string('primary')->nullable();
            $table->string('secondary')->nullable();
            $table->string('references')->nullable();
            $table->string('coordenadas')->nullable();
            $table->string('maps')->nullable();

            $table->tinyInteger('status')->default(Address::PUBLICADO);

            $table->foreignId('district_id')->constrained()->onDelete('cascade');

            $table->boolean('is_default')->default(false);
            /*
                $table->unsignedInteger('district_id')->default(150101);
                $table->foreign('district_id')->references('id')->on('districts');
            */

            $table->morphs('addressable');

            /* $table->morphs('addressable'); equivale a las tres lineas de abajo */

            /*  
                $table->unsignedBigInteger('addressable_id');
                $table->string('addressable_type');
                $table->index(['addressable_id', 'addressable_type']);
            */

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('addresses');
    }
};
