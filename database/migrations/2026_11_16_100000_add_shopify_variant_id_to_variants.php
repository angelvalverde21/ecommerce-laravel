<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('variants', fn (Blueprint $table) => $table->string('shopify_variant_id', 191)->nullable()->index());
    }

    public function down(): void
    {
        Schema::table('variants', function (Blueprint $table) {
            $table->dropIndex(['shopify_variant_id']);
            $table->dropColumn('shopify_variant_id');
        });
    }
};
