<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\{DB, Schema};

return new class extends Migration {
    public function up(): void
    {
        Schema::create('tasks', function (Blueprint $table) {
            $table->id(); $table->foreignId('store_id')->constrained()->restrictOnDelete();
            $table->morphs('taskable');
            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('completed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('title', 180); $table->text('description')->nullable();
            $table->date('due_date')->nullable(); $table->string('status', 20)->default('pending');
            $table->text('last_note')->nullable(); $table->timestamp('completed_at')->nullable();
            $table->unsignedInteger('version')->default(1); $table->timestamps();
            $table->index(['store_id', 'assigned_to', 'status']);
        });
        Schema::create('task_events', function (Blueprint $table) {
            $table->id(); $table->foreignId('task_id')->constrained()->restrictOnDelete();
            $table->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('type', 25); $table->json('changes'); $table->text('note')->nullable(); $table->timestamp('created_at');
        });
        DB::table('photo_session_tasks')->orderBy('id')->chunkById(500, function ($tasks) {
            $stores = DB::table('photo_sessions')->whereIn('id', $tasks->pluck('photo_session_id'))->pluck('store_id', 'id');
            foreach ($tasks as $task) {
                $row = (array) $task; unset($row['photo_session_id']);
                DB::table('tasks')->insert([...$row, 'store_id' => $stores[$task->photo_session_id], 'taskable_type' => 'photo_session', 'taskable_id' => $task->photo_session_id]);
            }
        });
        DB::table('photo_session_task_events')->orderBy('id')->chunkById(500, function ($events) {
            foreach ($events as $event) {
                $row = (array) $event; unset($row['photo_session_task_id']);
                DB::table('task_events')->insert([...$row, 'task_id' => $event->photo_session_task_id]);
            }
        });
        if (DB::table('tasks')->count() !== DB::table('photo_session_tasks')->count() || DB::table('task_events')->count() !== DB::table('photo_session_task_events')->count()) {
            throw new RuntimeException('La copia de tareas no coincide; se conservaron las tablas originales.');
        }
        Schema::drop('photo_session_task_events'); Schema::drop('photo_session_tasks');
    }
    public function down(): void
    {
        if (DB::table('tasks')->where('taskable_type', '!=', 'photo_session')->exists()) throw new RuntimeException('Existen tareas de otros módulos; no se puede revertir sin perder información.');
        (require __DIR__.'/2026_11_17_200000_create_photo_session_tasks_tables.php')->up();
        DB::table('tasks')->orderBy('id')->chunkById(500, function ($tasks) {
            foreach ($tasks as $task) {
                $row = (array) $task; unset($row['store_id'], $row['taskable_type'], $row['taskable_id']);
                DB::table('photo_session_tasks')->insert([...$row, 'photo_session_id' => $task->taskable_id]);
            }
        });
        DB::table('task_events')->orderBy('id')->chunkById(500, function ($events) {
            foreach ($events as $event) {
                $row = (array) $event; unset($row['task_id']);
                DB::table('photo_session_task_events')->insert([...$row, 'photo_session_task_id' => $event->task_id]);
            }
        });
        Schema::drop('task_events'); Schema::drop('tasks');
    }
};
