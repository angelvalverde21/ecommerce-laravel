<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('import_shopify_event_attachments', fn (Blueprint $table) => $table->json('download_metadata')->nullable());
    }

    public function down(): void
    {
        Schema::table('import_shopify_event_attachments', fn (Blueprint $table) => $table->dropColumn('download_metadata'));
    }
};
