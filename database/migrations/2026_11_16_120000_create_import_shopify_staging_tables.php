<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('import_shopify_runs')) Schema::create('import_shopify_runs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignId('store_id')->constrained()->restrictOnDelete();
            $table->foreignId('requested_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('shop_domain');
            $table->string('api_version', 20);
            $table->string('status', 20)->default('queued');
            $table->boolean('access_verified')->default(false);
            $table->string('current_task')->nullable();
            $table->string('error_code')->nullable();
            $table->text('error_message')->nullable();
            $table->json('throttle')->nullable();
            $table->timestamp('heartbeat_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->unsignedBigInteger('active_store_id')->nullable()->storedAs("CASE WHEN status IN ('completed', 'cancelled') THEN NULL ELSE store_id END")->unique();
            $table->timestamps();
        });
        if (!Schema::hasTable('import_shopify_tasks')) Schema::create('import_shopify_tasks', function (Blueprint $table) {
            $table->id();
            $table->foreignUuid('run_id')->constrained('import_shopify_runs')->cascadeOnDelete();
            $table->string('kind', 50);
            $table->string('owner_id', 191);
            $table->string('status', 20)->default('queued');
            $table->text('cursor')->nullable();
            $table->string('operation_id', 191)->nullable();
            $table->unsignedBigInteger('file_offset')->default(0);
            $table->unsignedBigInteger('processed_rows')->default(0);
            $table->unsignedBigInteger('expected_rows')->nullable();
            $table->unsignedBigInteger('expected_roots')->nullable();
            $table->unsignedBigInteger('processed_roots')->default(0);
            $table->timestamps();
            $table->unique(['run_id', 'kind', 'owner_id'], 'import_shopify_task_identity');
            $table->index(['run_id', 'status']);
        });
        foreach (['orders', 'order_items', 'shipping_lines', 'discounts', 'transactions', 'fulfillments', 'fulfillment_items', 'fulfillment_events', 'refunds', 'refund_items', 'refund_shipping_lines', 'refund_adjustments', 'products', 'variants', 'images', 'customers', 'addresses', 'metafields', 'events', 'records'] as $entity) {
            if (!Schema::hasTable('import_shopify_'.$entity)) Schema::create('import_shopify_'.$entity, function (Blueprint $table) use ($entity) {
                $table->id();
                $table->foreignUuid('run_id')->constrained('import_shopify_runs')->cascadeOnDelete();
                $table->string('shopify_id', 191);
                $table->string('parent_shopify_id', 191)->nullable();
                $table->json('payload');
                $table->timestamps();
                $table->unique(['run_id', 'shopify_id']);
                $table->index(['run_id', 'parent_shopify_id'], 'is_'.$entity.'_parent');
            });
            // MySQL DDL is not transactional: allow recovery if an earlier attempt stopped after CREATE TABLE.
            $hasParentIndex = collect(Schema::getIndexes('import_shopify_'.$entity))->contains(fn ($index) => $index['columns'] === ['run_id', 'parent_shopify_id']);
            if (!$hasParentIndex) Schema::table('import_shopify_'.$entity, fn (Blueprint $table) => $table->index(['run_id', 'parent_shopify_id'], 'is_'.$entity.'_parent'));
        }
    }

    public function down(): void
    {
        foreach (['orders', 'order_items', 'shipping_lines', 'discounts', 'transactions', 'fulfillments', 'fulfillment_items', 'fulfillment_events', 'refunds', 'refund_items', 'refund_shipping_lines', 'refund_adjustments', 'products', 'variants', 'images', 'customers', 'addresses', 'metafields', 'events', 'records', 'tasks', 'runs'] as $entity) Schema::dropIfExists('import_shopify_'.$entity);
    }
};
