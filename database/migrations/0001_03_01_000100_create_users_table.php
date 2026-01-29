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
        Schema::create('users', function (Blueprint $table) {
            $table->id();

            $table->string('name');
            $table->string('email')->unique()->nullable();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');

            $table->tinyInteger('status')
                ->default(1)
                ->comment('1 = active, 0 = blocked');

            $table->string('phone')->nullable();

            // $table->foreignId('identity_id')->nullable()
            // ->constrained()
            // ->cascadeOnDelete(); // DNI, RUC, CE, etc

            // Impide eliminar un registro de identities si está siendo usado
            // por algún usuario. Primero se debe desvincular o eliminar
            // el usuario que lo referencia.
            // $table->foreignId('identity_id') // DNI, RUC, CE, etc
            //     ->nullable()
            //     ->constrained('identities')
            //     ->restrictOnDelete(); // impide eliminar identity si está siendo usado por user

            $table->foreignId('identity_id') // DNI, RUC, CE, etc
                ->nullable()
                ->constrained('identities')
                ->nullOnDelete(); // deja identity_id en null al borrar identity


            $table->string('document_number', 20)->nullable()->unique();
            $table->rememberToken();
            $table->timestamps();
        });


        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignId('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('sessions');
    }
};
