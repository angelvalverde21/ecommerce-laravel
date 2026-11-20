<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('import_shopify_order_updates', function (Blueprint $table) {
            $table->id();
            $table->foreignUuid('run_id')->constrained('import_shopify_runs')->cascadeOnDelete();
            $table->foreignId('requested_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('status', 20)->default('queued');
            $table->dateTime('search_from');
            $table->dateTime('search_until');
            $table->text('cursor')->nullable();
            $table->unsignedBigInteger('checked_orders')->default(0);
            $table->unsignedBigInteger('added_orders')->default(0);
            $table->unsignedBigInteger('task_baseline')->default(0);
            $table->timestamp('scan_completed_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamps();
            $table->index(['run_id', 'status'], 'is_order_update_run_status');
        });
    }

    public function down(): void { Schema::dropIfExists('import_shopify_order_updates'); }
};
