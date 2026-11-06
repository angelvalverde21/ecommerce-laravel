<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasColumn('inventories', 'user_id') && !Schema::hasColumn('inventories', 'created_user_id')) {
            Schema::table('inventories', fn (Blueprint $table) => $table->renameColumn('user_id', 'created_user_id'));
        }
        foreach (['status', 'comment', 'started_at', 'completed_at'] as $column) {
            if (Schema::hasColumn('inventories', $column)) continue;
            Schema::table('inventories', function (Blueprint $table) use ($column) {
                if ($column === 'status') { $table->enum('status', ['draft', 'in_progress', 'completed', 'cancelled'])->default('draft'); $table->index(['store_id', 'status']); }
                elseif ($column === 'comment') $table->text('comment')->nullable();
                else $table->timestamp($column)->nullable();
            });
        }
    }

    public function down(): void
    {
        // Keep historical inventory data and author references when rolling back compatibility changes.
    }
};
