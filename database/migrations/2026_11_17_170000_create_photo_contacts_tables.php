<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('photo_contacts', function (Blueprint $table) {
            $table->id(); $table->foreignId('store_id')->constrained()->restrictOnDelete();
            $table->string('kind', 20); $table->string('name', 180);
            $table->string('phone', 40)->nullable(); $table->string('email')->nullable();
            $table->string('instagram', 120)->nullable(); $table->string('portfolio_url', 500)->nullable();
            $table->string('company')->nullable(); $table->text('notes')->nullable();
            $table->boolean('active')->default(true); $table->timestamps();
            $table->index(['store_id', 'kind', 'active']);
        });
        Schema::table('photo_sessions', function (Blueprint $table) {
            $table->foreignId('photographer_contact_id')->nullable()->constrained('photo_contacts')->nullOnDelete();
        });
        Schema::create('photo_session_models', function (Blueprint $table) {
            $table->id(); $table->foreignId('photo_session_id')->constrained()->cascadeOnDelete();
            $table->foreignId('photo_contact_id')->constrained()->restrictOnDelete();
            $table->unique(['photo_session_id', 'photo_contact_id']);
        });
    }
    public function down(): void
    {
        Schema::dropIfExists('photo_session_models');
        Schema::table('photo_sessions', function (Blueprint $table) { $table->dropConstrainedForeignId('photographer_contact_id'); });
        Schema::dropIfExists('photo_contacts');
    }
};
