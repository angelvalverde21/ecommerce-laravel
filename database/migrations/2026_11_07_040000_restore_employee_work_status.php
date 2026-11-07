<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->boolean('status')->default(true);
            $table->index(['store_id', 'status']);
        });

        DB::table('employees')->orderBy('id')->chunkById(100, function ($employees) {
            foreach ($employees as $employee) {
                $status = DB::table('users')->where('id', $employee->user_id)->value('status');
                if ($status === null) {
                    throw new RuntimeException('El empleado debe tener una cuenta válida para inicializar su estado laboral.');
                }
                DB::table('employees')->where('id', $employee->id)->update(['status' => (bool) $status]);
            }
        });
    }

    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->dropIndex(['store_id', 'status']);
            $table->dropColumn('status');
        });
    }
};
