<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('photo_session_tasks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('photo_session_id')->constrained()->restrictOnDelete();
            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('completed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('title', 180);
            $table->text('description')->nullable();
            $table->date('due_date')->nullable();
            $table->string('status', 20)->default('pending');
            $table->text('last_note')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->unsignedInteger('version')->default(1);
            $table->timestamps();
            $table->index(['photo_session_id', 'status', 'due_date']);
            $table->index(['assigned_to', 'status']);
        });
        Schema::create('photo_session_task_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('photo_session_task_id')->constrained()->restrictOnDelete();
            $table->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('type', 25);
            $table->json('changes');
            $table->text('note')->nullable();
            $table->timestamp('created_at');
        });
    }
    public function down(): void
    {
        Schema::dropIfExists('photo_session_task_events');
        Schema::dropIfExists('photo_session_tasks');
    }
};
