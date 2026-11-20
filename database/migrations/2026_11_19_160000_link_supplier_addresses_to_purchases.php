<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\{DB, Schema};

return new class extends Migration {
    public function up(): void
    {
        foreach (['purchases', 'manufactures'] as $name) {
            Schema::table($name, fn (Blueprint $table) => $table->foreignId('supplier_address_id')->nullable()
                ->constrained('addresses')->nullOnDelete());
        }
        // Preserve existing defaults; choose the oldest active address only when none was selected.
        DB::table('addresses')->where('addressable_type', (new \App\Models\Supplier)->getMorphClass())
            ->where('status', 1)->select('addressable_id')->distinct()->orderBy('addressable_id')->get()
            ->each(function ($owner) {
                $query = DB::table('addresses')->where('addressable_type', (new \App\Models\Supplier)->getMorphClass())
                    ->where('addressable_id', $owner->addressable_id);
                $id = (clone $query)->where('status', 1)->orderByDesc('is_default')->orderBy('id')->value('id');
                $query->update(['is_default' => false]);
                DB::table('addresses')->where('id', $id)->update(['is_default' => true]);
            });
    }

    public function down(): void
    {
        foreach (['purchases', 'manufactures'] as $name) Schema::table($name, fn (Blueprint $table) => $table->dropConstrainedForeignId('supplier_address_id'));
    }
};
