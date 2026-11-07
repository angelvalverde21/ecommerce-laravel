<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::table('employees')->whereNull('user_id')->exists()) {
            throw new RuntimeException('Asocia una cuenta de acceso a cada empleado antes de aplicar este cambio.');
        }
        if (DB::table('employees')->select('user_id')->groupBy('user_id')->havingRaw('COUNT(*) > 1')->exists()) {
            throw new RuntimeException('Revisa las cuentas compartidas por más de un empleado antes de aplicar este cambio.');
        }
        $foreign = collect(Schema::getForeignKeys('employees'))->first(fn ($key) => $key['columns'] === ['user_id']);
        Schema::table('employees', function (Blueprint $table) use ($foreign) {
            if ($foreign) {
                $table->dropForeign(DB::getDriverName() === 'sqlite' ? ['user_id'] : $foreign['name']);
            }
            $table->unsignedBigInteger('user_id')->nullable(false)->change();
            $table->foreign('user_id')->references('id')->on('users')->restrictOnDelete();
        });
        $unique = collect(Schema::getIndexes('employees'))->contains(fn ($index) => $index['unique'] && $index['columns'] === ['user_id']);
        if (! $unique) {
            Schema::table('employees', fn (Blueprint $table) => $table->unique('user_id'));
        }
    }

    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->unsignedBigInteger('user_id')->nullable()->change();
            $table->foreign('user_id')->references('id')->on('users')->nullOnDelete();
        });
    }
};
