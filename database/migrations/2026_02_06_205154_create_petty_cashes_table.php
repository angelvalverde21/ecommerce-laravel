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
        Schema::create('petty_cashes', function (Blueprint $table) {

            $table->id();

            $table->foreignId('store_id')
                ->nullable()
                ->constrained()
                ->nullOnDelete();

            $table->foreignId('employee_id')
                ->nullable()
                ->constrained()
                ->nullOnDelete(); // responsable

            $table->foreignId('gateway_id')->nullable()->constrained()->nullOnDelete('cascade');

            $table->decimal('amount_assigned', 12, 2);
            $table->decimal('balance', 12, 2);

            $table->enum('status', ['open', 'closed'])->default('open');

            $table->timestamp('opened_at')->nullable();
            $table->timestamp('closed_at')->nullable();

            // Reglas de integridad útiles
            $table->unique(['store_id', 'employee_id'], 'unique_open_petty_cash_per_employee');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('petty_cashes');
    }
};
