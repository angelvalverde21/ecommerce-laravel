<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('projects', function (Blueprint $t) {
            $t->id(); $t->foreignId('store_id')->constrained()->restrictOnDelete();
            $t->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $t->string('name', 180); $t->text('description')->nullable();
            $t->string('status', 30)->default('planning');
            $t->date('starts_on')->nullable(); $t->date('ends_on')->nullable(); $t->timestamps();
            $t->index(['store_id', 'status']);
        });
        Schema::create('projects_collections', function (Blueprint $t) {
            $t->id(); $t->foreignId('project_id')->constrained('projects')->restrictOnDelete();
            $t->string('name', 180); $t->text('description')->nullable();
            $t->date('launch_on')->nullable(); $t->timestamps();
        });
        Schema::create('projects_processes', function (Blueprint $t) {
            $t->id(); $t->foreignId('store_id')->constrained()->restrictOnDelete();
            $t->foreignId('collection_id')->constrained('projects_collections')->restrictOnDelete();
            $t->string('kind', 30); $t->string('source_type', 30); $t->unsignedBigInteger('source_id')->nullable();
            $t->string('name'); $t->string('status', 30)->default('pending');
            $t->date('starts_on')->nullable(); $t->date('due_on')->nullable(); $t->text('notes')->nullable();
            $t->foreignId('responsible_id')->nullable()->constrained('users')->nullOnDelete();
            $t->timestamps(); $t->unique(['source_type', 'source_id'], 'projects_process_source_unique');
        });
        Schema::create('projects_products', function (Blueprint $t) {
            $t->id(); $t->foreignId('collection_id')->constrained('projects_collections')->restrictOnDelete();
            $t->foreignId('product_id')->constrained()->restrictOnDelete(); $t->timestamps();
            $t->unique(['collection_id', 'product_id']);
        });
        Schema::create('projects_sales', function (Blueprint $t) {
            $t->id(); $t->foreignId('process_id')->constrained('projects_processes')->restrictOnDelete();
            $t->foreignId('order_id')->constrained()->restrictOnDelete(); $t->string('line_key', 191);
            $t->foreignId('variant_id')->constrained()->restrictOnDelete(); $t->unsignedInteger('quantity');
            $t->decimal('unit_price', 18, 6); $t->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $t->timestamps(); $t->unique(['process_id', 'order_id', 'line_key'], 'projects_sales_source_unique');
            $t->index(['order_id', 'line_key']);
        });
        Schema::create('projects_receipts', function (Blueprint $t) {
            $t->id(); $t->foreignId('sale_id')->constrained('projects_sales')->restrictOnDelete();
            $t->string('direction', 3); $t->decimal('amount', 14, 2); $t->date('date');
            $t->string('reference', 180); $t->text('notes')->nullable();
            $t->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete(); $t->timestamps();
            $t->unique(['sale_id', 'direction', 'reference'], 'projects_receipt_reference_unique');
        });
    }

    public function down(): void
    {
        foreach (['projects_receipts', 'projects_sales', 'projects_products', 'projects_processes', 'projects_collections', 'projects'] as $table) Schema::dropIfExists($table);
    }
};
