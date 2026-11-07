<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::table('employees')->leftJoin('users', 'users.id', '=', 'employees.user_id')->whereNull('users.id')->exists()) {
            throw new RuntimeException('Cada empleado debe tener una cuenta válida antes de retirar los campos personales duplicados.');
        }
        $storeIndex = collect(Schema::getIndexes('employees'))->contains(fn ($index) => $index['columns'] === ['store_id']);
        if (! $storeIndex) {
            Schema::table('employees', fn (Blueprint $table) => $table->index('store_id', 'employees_store_lookup_index'));
        }
        Schema::table('employees', function (Blueprint $table) {
            $table->dropForeign(['identity_id']);
            $table->dropIndex(['store_id', 'document_number']);
            $table->dropIndex(['store_id', 'status']);
            $table->dropColumn(['phone', 'identity_id', 'document_number', 'status', 'roles']);
        });
    }

    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->string('phone')->nullable();
            $table->foreignId('identity_id')->nullable()->constrained('identities')->nullOnDelete();
            $table->string('document_number', 50)->nullable();
            $table->boolean('status')->default(true);
            $table->json('roles')->nullable();
            $table->index(['store_id', 'document_number']);
            $table->index(['store_id', 'status']);
        });
        DB::table('employees')->orderBy('id')->chunkById(100, function ($employees) {
            foreach ($employees as $employee) {
                $user = \App\Models\User::with('roles')->findOrFail($employee->user_id);
                DB::table('employees')->where('id', $employee->id)->update([
                    'phone' => $user->phone,
                    'identity_id' => $user->identity_id,
                    'document_number' => $user->document_number,
                    'status' => $user->status,
                    'roles' => json_encode($user->getRoleNames()->all(), JSON_THROW_ON_ERROR),
                ]);
            }
        });
    }
};
