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
        Schema::create('colors', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->unsignedTinyInteger('status')->default(Status::BORRADOR)->comment('Status::PUBLICADO = 1, Status::BORRADOR = 3, Status::ELIMINADO = 0, Status::ARCHIVADO = 2]'); //desde -128 a 127
            $table->unsignedTinyInteger('sort_order'); //del 1 al 255
            $table->unsignedInteger('quantity')->default(0);
            $table->foreignId('product_id')->nullable()->constrained()->onDelete('cascade');
            $table->unique(['name', 'product_id']);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('colors');
    }
};
