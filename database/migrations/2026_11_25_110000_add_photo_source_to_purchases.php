<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::table('purchases', function (Blueprint $table) {
            $table->string('photo_source_kind', 20)->nullable();
            $table->unsignedBigInteger('photo_source_id')->nullable();
            $table->string('photo_recipient_name')->nullable();
            $table->index(['photo_source_kind', 'photo_source_id'], 'purchase_photo_source');
        });
    }
    public function down(): void {
        Schema::table('purchases', function (Blueprint $table) {
            $table->dropIndex('purchase_photo_source');
            $table->dropColumn(['photo_source_kind', 'photo_source_id', 'photo_recipient_name']);
        });
    }
};
