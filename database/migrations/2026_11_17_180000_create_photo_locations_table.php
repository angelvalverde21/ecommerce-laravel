<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('photo_locations', function (Blueprint $table) {
            $table->id(); $table->foreignId('store_id')->constrained()->restrictOnDelete();
            $table->string('name', 180); $table->string('type', 25);
            $table->string('address', 500)->nullable(); $table->string('district', 120)->nullable(); $table->string('city', 120)->nullable();
            $table->string('contact_name', 180)->nullable(); $table->string('phone', 40)->nullable(); $table->string('email')->nullable();
            $table->string('reference_url', 1000)->nullable();
            $table->decimal('rate', 12, 2)->nullable(); $table->string('rate_unit', 15)->default('hour');
            $table->text('notes')->nullable(); $table->boolean('active')->default(true); $table->timestamps();
            $table->index(['store_id', 'type', 'active']);
        });
        Schema::table('photo_sessions', function (Blueprint $table) {
            $table->foreignId('photo_location_id')->nullable()->constrained('photo_locations')->restrictOnDelete();
        });
    }
    public function down(): void
    {
        Schema::table('photo_sessions', function (Blueprint $table) { $table->dropConstrainedForeignId('photo_location_id'); });
        Schema::dropIfExists('photo_locations');
    }
};
