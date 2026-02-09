<?php

use App\Models\Payment;
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
        Schema::create('payments', function (Blueprint $table) {
            $table->id();

            $table->foreignId('store_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->cascadeOnDelete();
            $table->decimal('amount', 10, 2);
            $table->string('comment')->nullable();
            
            $table->foreignId('gateway_id')->nullable()->constrained()->cascadeOnDelete();
            
            $table->enum('method', [
                'cash',
                'yape',
                'plin',
                'credit_card',
                'bank_transfer',
                'paypal',
            ])->default('cash');

            $table->enum('status', [
                'unpaid',
                'pending',
                'failed',
                'expired',
                'paid',
                'refunding',
                'refunded',
            ])->default('unpaid');
            $table->timestamp('date')->nullable();
            $table->morphs('paymentable'); // Polimórfica para asociar con compras, ventas, etc.
            $table->enum('direction', ['in', 'out'])->default('out');


            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
