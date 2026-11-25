<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('photo_session_expenses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('photo_session_id')->constrained()->restrictOnDelete();
            $table->foreignId('payment_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('source_kind', 20);
            $table->unsignedBigInteger('source_id');
            $table->string('source_name');
            $table->string('recipient_name');
            $table->unsignedBigInteger('supplier_id')->nullable();
            $table->uuid('request_key')->unique();
            $table->char('payload_hash', 64);
            $table->timestamps();
            $table->index(['photo_session_id', 'source_kind', 'source_id'], 'photo_expense_source');
        });
    }
    public function down(): void { Schema::dropIfExists('photo_session_expenses'); }
};
