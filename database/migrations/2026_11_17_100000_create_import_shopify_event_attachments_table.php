<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('import_shopify_runs', fn (Blueprint $table) => $table->unsignedTinyInteger('timeline_version')->default(1));
        Schema::create('import_shopify_event_attachments', function (Blueprint $table) {
            $table->id();
            $table->foreignUuid('run_id')->constrained('import_shopify_runs')->cascadeOnDelete();
            $table->string('task_key', 64);
            $table->string('shopify_id', 191);
            $table->string('parent_shopify_id', 191); // Original comment ID.
            $table->string('order_shopify_id', 191);
            $table->json('payload');
            $table->string('status', 20)->default('pending');
            $table->string('disk')->default('local');
            $table->string('local_path')->nullable();
            $table->unsignedBigInteger('bytes')->nullable();
            $table->string('sha256', 64)->nullable();
            $table->string('mime_type')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamp('downloaded_at')->nullable();
            $table->timestamps();
            $table->unique(['run_id', 'task_key'], 'is_event_attachment_identity');
            $table->index(['run_id', 'parent_shopify_id'], 'is_attachment_event');
            $table->index(['run_id', 'order_shopify_id'], 'is_attachment_order');
            $table->index(['run_id', 'status'], 'is_attachment_status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('import_shopify_event_attachments');
        Schema::table('import_shopify_runs', fn (Blueprint $table) => $table->dropColumn('timeline_version'));
    }
};
