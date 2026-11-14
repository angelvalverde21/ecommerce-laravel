<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('delivery_trackings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->constrained()->restrictOnDelete();
            $table->string('trackable_type');
            $table->unsignedBigInteger('trackable_id');
            $table->date('handed_over_on')->nullable();
            $table->date('expected_on');
            $table->date('received_on')->nullable();
            $table->unsignedInteger('revision')->default(0);
            $table->timestamps();
            $table->unique(['trackable_type', 'trackable_id']);
            $table->index(['store_id', 'received_on', 'expected_on']);
        });
        Schema::create('delivery_tracking_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('delivery_tracking_id')->constrained()->restrictOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('actor_name');
            $table->string('type', 25);
            $table->json('changes');
            $table->text('reason')->nullable();
            $table->timestamp('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('delivery_tracking_events');
        Schema::dropIfExists('delivery_trackings');
    }
};
