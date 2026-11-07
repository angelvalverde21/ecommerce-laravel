<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('orders', 'store_id') && DB::table('orders')->exists()) {
            throw new RuntimeException('Asigna una tienda a los pedidos antiguos antes de ampliar orders.');
        }

        if (Schema::hasColumns('orders', ['store_id', 'shopify_order_id'])) {
            $duplicates = DB::table('orders')->whereNotNull('shopify_order_id')
                ->select('store_id', 'shopify_order_id')->groupBy('store_id', 'shopify_order_id')
                ->havingRaw('COUNT(*) > 1')->exists();
            if ($duplicates) {
                throw new RuntimeException('Resuelve los pedidos Shopify duplicados antes de ampliar la estructura.');
            }
        }

        $columns = Schema::getColumnListing('orders');
        Schema::table('orders', function (Blueprint $table) use ($columns) {
            if (! in_array('store_id', $columns)) {
                $table->foreignId('store_id')->constrained()->restrictOnDelete();
            }
            if (! in_array('user_id', $columns)) {
                $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            }
            if (! in_array('shopify_order_id', $columns)) {
                $table->string('shopify_order_id', 191)->nullable();
            }
            if (! in_array('shopify_order_name', $columns)) {
                $table->string('shopify_order_name')->nullable();
            }
            if (! in_array('variants', $columns)) {
                $table->json('variants')->nullable();
            }
            if (! in_array('courier_address_id', $columns)) {
                $table->foreignId('courier_address_id')->nullable()->constrained('addresses')->nullOnDelete();
            }
            if (! in_array('delivery_type', $columns)) {
                $table->string('delivery_type', 30)->nullable();
            }
            if (! in_array('is_cod', $columns)) {
                $table->boolean('is_cod')->default(false);
            }
            if (! in_array('freight_collect', $columns)) {
                $table->boolean('freight_collect')->default(false);
            }
            foreach (['subtotal', 'igv', 'delivery_cost', 'total'] as $column) {
                if (! in_array($column, $columns)) {
                    $table->decimal($column, 14, 2)->default(0);
                }
            }
            if (! in_array('status', $columns)) {
                $table->string('status', 30)->default('registrado')->index();
            }
            if (! in_array('currency_code', $columns)) {
                $table->char('currency_code', 3)->default('PEN');
            }
        });
        $hasUnique = collect(Schema::getIndexes('orders'))->contains(fn ($index) => $index['unique'] && $index['columns'] === ['store_id', 'shopify_order_id']);
        if (! $hasUnique) {
            Schema::table('orders', fn (Blueprint $table) => $table->unique(['store_id', 'shopify_order_id'], 'orders_store_shopify_unique'));
        }

        $addressColumns = Schema::getColumnListing('addresses');
        Schema::table('addresses', function (Blueprint $table) use ($addressColumns) {
            $table->unsignedBigInteger('district_id')->nullable()->change();
            if (! in_array('purpose', $addressColumns)) {
                $table->string('purpose', 30)->nullable();
            }
            foreach (['country_code', 'city', 'province', 'postal_code'] as $column) {
                if (! in_array($column, $addressColumns)) {
                    $table->string($column)->nullable();
                }
            }
        });

        Schema::create('shopify_order_imports', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignId('store_id')->constrained()->restrictOnDelete();
            $table->string('status', 30)->default('exporting');
            $table->string('phase', 20)->default('export');
            $table->string('operation_id', 191)->nullable();
            $table->unsignedBigInteger('expected_orders')->nullable();
            $table->unsignedBigInteger('expected_rows')->nullable();
            $table->unsignedBigInteger('active_store_id')->nullable()->storedAs("CASE WHEN status IN ('completed', 'cancelled') THEN NULL ELSE store_id END")->unique();
            $table->unsignedBigInteger('file_offset')->default(0);
            $table->unsignedBigInteger('last_row_id')->default(0);
            foreach (['staged_rows', 'imported_orders', 'skipped_orders', 'unmatched_variants', 'unresolved_addresses'] as $column) {
                $table->unsignedBigInteger($column)->default(0);
            }
            $table->string('error_code')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
        });
        Schema::create('shopify_order_import_rows', function (Blueprint $table) {
            $table->id();
            $table->foreignUuid('import_id')->constrained('shopify_order_imports')->cascadeOnDelete();
            $table->string('source_id', 191);
            $table->string('parent_id', 191)->nullable();
            $table->string('type', 30);
            $table->json('payload');
            $table->unique(['import_id', 'source_id']);
            $table->index(['import_id', 'parent_id']);
        });
        Schema::create('order_shopify_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->unique()->constrained()->cascadeOnDelete();
            $table->foreignUuid('import_id')->constrained('shopify_order_imports')->restrictOnDelete();
            $table->string('financial_status')->nullable();
            $table->string('fulfillment_status')->nullable();
            $table->timestamp('source_updated_at')->nullable();
            $table->json('source_payload');
            $table->timestamps();
        });
        Schema::create('order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('variant_id')->nullable()->constrained()->nullOnDelete();
            $table->string('shopify_line_item_id', 191);
            $table->string('shopify_variant_id', 191)->nullable();
            $table->string('sku')->nullable();
            $table->string('name');
            $table->unsignedInteger('quantity');
            $table->decimal('unit_price', 18, 6);
            $table->decimal('line_total', 18, 2);
            $table->char('currency_code', 3);
            $table->json('source_payload');
            $table->timestamps();
            $table->unique(['order_id', 'shopify_line_item_id']);
        });
    }

    public function down(): void
    {
        throw new RuntimeException('Esta migración conserva pedidos y direcciones históricos. Para revertirla, restaura el respaldo previo.');
    }
};
