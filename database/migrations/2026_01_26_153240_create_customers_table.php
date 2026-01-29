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
        Schema::create('customers', function (Blueprint $table) {

            $table->id();

            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();// un customer solo debe estar relacionado a un user
            // $table->string('origen')->nullable(); //para ver de donde vino el usuario puede ser de una tienda online, facebook, instagram, etc

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('customers');
    }
};
