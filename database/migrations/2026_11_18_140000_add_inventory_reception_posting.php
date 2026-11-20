<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\{DB, Schema};

return new class extends Migration {
    public function up(): void
    {
        Schema::table('inventory_receptions', function (Blueprint $table) {
            // Existing documents already have ledger entries. Never post them a second time.
            $table->string('status', 20)->default('posted')->index();
            $table->unsignedInteger('revision')->default(1);
            $table->timestamp('posted_at')->nullable();
            $table->foreignId('posted_by')->nullable()->constrained('users')->nullOnDelete();
        });
        DB::table('inventory_receptions')->whereNotNull('reversed_at')->update(['status' => 'reversed']);
        DB::table('inventory_receptions')->update(['posted_at' => DB::raw('created_at'), 'posted_by' => DB::raw('received_by')]);
        DB::table('inventory_receptions')->where('is_historical', true)->update(['posted_at' => DB::raw('(SELECT MIN(k.created_at) FROM kardexes k INNER JOIN inventory_reception_items i ON i.id = k.inventory_reception_item_id WHERE i.inventory_reception_id = inventory_receptions.id AND k.direction = \'in\')')]);
    }
    public function down(): void
    {
        Schema::table('inventory_receptions', function (Blueprint $table) {
            $table->dropConstrainedForeignId('posted_by');
            $table->dropIndex(['status']);
            $table->dropColumn(['status', 'revision', 'posted_at']);
        });
    }
};
