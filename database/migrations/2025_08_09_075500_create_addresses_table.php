<?php

use App\Models\Address;
use App\Models\Status;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    /*
         name
        identity_id
        document_number
        type
        primary
        secondary
        references
        latitud
        longitud
        url_maps
        status
        is_default
        district_id
        sort_order
        
        */
    public function up(): void
    {
        Schema::create('addresses', function (Blueprint $table) {

            $table->id();

            $table->string('name');
            $table->foreignId('identity_id')->nullable()->constrained()->restrictOnDelete(); //DNI, RUC, CE, etc
            $table->string('document_number')->nullable();
            $table->string('phone')->nullable();
            $table->string('type')->nullable()->default(Address::DEFAULT_TYPE_ADDRESS['casa']); //casa, oficina, trabajo, etc
            $table->string('primary')->nullable(); //direccion principal, por ejemplo: Av. Siempre Viva 123
            $table->string('secondary')->nullable(); //direccion secundaria, por ejemplo: Urb. Los Robles
            $table->string('references')->nullable();
            $table->string('latitud')->nullable();
            $table->string('longitud')->nullable();
            $table->string('url_maps')->nullable();
            $table->string('reception_hours')->nullable();
            $table->string('sunday_hours')->nullable();

            $table->tinyInteger('status')->default(Status::ACTIVE)->comment('Address::INACTIVE = 0, Address::ACTIVE = 1'); //desde -128 a 127

            $table->boolean('is_default')->default(false);

            $table->foreignId('district_id')->constrained()->restrictOnDelete();

            $table->integer('sort_order')->default(0);

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
