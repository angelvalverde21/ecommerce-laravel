<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $invalid = DB::table('employees')->leftJoin('users', 'users.id', '=', 'employees.user_id')
            ->where(fn ($query) => $query->whereNull('users.id')->orWhereNull('users.name')->orWhereNull('users.email'))
            ->exists();
        if ($invalid) {
            throw new RuntimeException('Completa la cuenta, nombre y correo de cada empleado antes de retirar las columnas duplicadas.');
        }
        Schema::table('employees', fn (Blueprint $table) => $table->dropColumn(['name', 'email']));
    }

    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->string('name')->nullable();
            $table->string('email')->nullable();
        });
        DB::table('employees')->orderBy('id')->chunkById(100, function ($employees) {
            foreach ($employees as $employee) {
                $user = DB::table('users')->find($employee->user_id);
                DB::table('employees')->where('id', $employee->id)->update(['name' => $user?->name, 'email' => $user?->email]);
            }
        });
    }
};
