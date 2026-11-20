<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\{DB, Schema};

return new class extends Migration {
    public function up(): void
    {
        Schema::table('inventory_receptions', fn (Blueprint $table) => $table->unsignedInteger('number')->nullable());
        $sources = DB::table('inventory_receptions')->select('store_id', 'source_type', 'source_id')->distinct()->get();
        foreach ($sources as $source) {
            $receipts = DB::table('inventory_receptions')->where('store_id', $source->store_id)
                ->where('source_type', $source->source_type)->where('source_id', $source->source_id)
                ->orderBy('received_on')->orderBy('created_at')->orderBy('id')->get(['id']);
            foreach ($receipts as $index => $receipt) DB::table('inventory_receptions')->where('id', $receipt->id)->update(['number' => $index + 1]);
        }
        Schema::table('inventory_receptions', function (Blueprint $table) {
            $table->unsignedInteger('number')->nullable(false)->change();
            $table->unique(['store_id', 'source_type', 'source_id', 'number'], 'reception_source_number_unique');
        });
    }
    public function down(): void
    {
        Schema::table('inventory_receptions', function (Blueprint $table) {
            $table->dropUnique('reception_source_number_unique');
            $table->dropColumn('number');
        });
    }
};
