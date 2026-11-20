<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('photo_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->constrained()->restrictOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('responsible_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('name', 180);
            $table->string('status', 25)->default('planned');
            $table->date('shoot_date')->nullable();
            $table->time('starts_at')->nullable();
            $table->time('ends_at')->nullable();
            $table->date('delivery_date')->nullable();
            $table->string('location')->nullable();
            $table->string('collection')->nullable();
            $table->string('photographer')->nullable();
            $table->text('team')->nullable();
            $table->text('brief')->nullable();
            $table->text('notes')->nullable();
            $table->decimal('budget', 12, 2)->nullable();
            $table->timestamps();
            $table->index(['store_id', 'status', 'shoot_date']);
        });
        Schema::create('photo_session_assets', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignId('photo_session_id')->constrained()->restrictOnDelete();
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('name');
            $table->string('kind', 15);
            $table->string('category', 15)->default('original');
            $table->boolean('selected')->default(false);
            $table->string('status', 20)->default('uploading');
            $table->string('mime_type', 120);
            $table->unsignedBigInteger('bytes');
            $table->string('bucket');
            $table->string('object_key')->unique();
            $table->text('upload_id')->nullable();
            $table->string('etag')->nullable();
            $table->json('previews')->nullable();
            $table->text('caption')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
            $table->index(['photo_session_id', 'status', 'created_at']);
        });
    }
    public function down(): void
    {
        Schema::dropIfExists('photo_session_assets');
        Schema::dropIfExists('photo_sessions');
    }
};
