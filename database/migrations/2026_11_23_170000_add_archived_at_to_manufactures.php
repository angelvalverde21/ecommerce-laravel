<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('manufactures', fn (Blueprint $table) => $table->timestamp('archived_at')->nullable()->index()->after('manufacture_end'));
    }
    public function down(): void
    {
        Schema::table('manufactures', fn (Blueprint $table) => $table->dropColumn('archived_at'));
    }
};
