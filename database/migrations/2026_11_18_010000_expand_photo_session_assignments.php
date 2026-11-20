<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\{DB, Schema};

return new class extends Migration {
    public function up(): void
    {
        Schema::create('photo_session_photographers', function (Blueprint $table) {
            $table->id(); $table->foreignId('photo_session_id')->constrained()->cascadeOnDelete();
            $table->foreignId('photo_contact_id')->constrained()->restrictOnDelete();
            $table->unique(['photo_session_id', 'photo_contact_id'], 'session_photographers_unique');
        });
        Schema::create('photo_session_locations', function (Blueprint $table) {
            $table->id(); $table->foreignId('photo_session_id')->constrained()->cascadeOnDelete();
            $table->foreignId('photo_location_id')->constrained()->restrictOnDelete();
            $table->unique(['photo_session_id', 'photo_location_id'], 'session_locations_unique');
        });
        DB::table('photo_sessions')->orderBy('id')->chunkById(500, function ($sessions) {
            foreach ($sessions as $session) {
                if ($session->photographer_contact_id) DB::table('photo_session_photographers')->insert(['photo_session_id' => $session->id, 'photo_contact_id' => $session->photographer_contact_id]);
                if ($session->photo_location_id) DB::table('photo_session_locations')->insert(['photo_session_id' => $session->id, 'photo_location_id' => $session->photo_location_id]);
            }
        });
    }
    public function down(): void
    {
        if (DB::table('photo_session_photographers')->select('photo_session_id')->groupBy('photo_session_id')->havingRaw('COUNT(*) > 1')->exists()
            || DB::table('photo_session_locations')->select('photo_session_id')->groupBy('photo_session_id')->havingRaw('COUNT(*) > 1')->exists()) {
            throw new RuntimeException('No se puede revertir mientras existan sesiones con múltiples asignaciones.');
        }
        Schema::dropIfExists('photo_session_locations'); Schema::dropIfExists('photo_session_photographers');
    }
};
