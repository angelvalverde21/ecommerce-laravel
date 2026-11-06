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

        Schema::create('inventory_sessions', function (Blueprint $table) {

            $table->id();

            $table->foreignId('inventory_id')
                ->constrained()
                ->restrictOnDelete();

            $table->foreignId('user_id') //Es el usuario que realiza el inventario, no necesariamente el que lo crea
                ->constrained()
                ->restrictOnDelete()->comment('el user_id es el que realiza el inventario, no necesariamente el que lo crea');

            $table->enum('status', [
                'open',
                'closed',
                'cancelled'
            ])->default('open');

            $table->timestamp('started_at')->useCurrent();
            $table->timestamp('ended_at')->nullable();

            $table->text('comment')->nullable();

            $table->timestamps();

            $table->index(['inventory_id', 'status']);

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inventory_sessions');
    }
};
