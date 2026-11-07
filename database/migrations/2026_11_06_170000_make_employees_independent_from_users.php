<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $employees = DB::table('employees')->get();
        $owners = [];
        foreach ($employees as $employee) {
            $stores = DB::table('store_user')->where('user_id', $employee->user_id)->distinct()->pluck('store_id');
            if ($stores->count() !== 1 || ! DB::table('users')->where('id', $employee->user_id)->whereNotNull('name')->exists()) {
                throw new RuntimeException('Revisa la tienda y el perfil del empleado '.$employee->id.' antes de independizar employees.');
            }
            $owners[$employee->id] = $stores->first();
        }
        $columns = Schema::getColumnListing('employees');
        Schema::table('employees', function (Blueprint $table) use ($columns) {
            $table->foreignId('store_id')->nullable()->constrained()->restrictOnDelete();
            $table->string('name')->nullable();
            $table->string('email')->nullable();
            $table->foreignId('identity_id')->nullable()->constrained('identities')->nullOnDelete();
            $table->string('document_number', 50)->nullable();
            $table->boolean('status')->default(true);
            $table->json('roles')->nullable();
            $table->date('date_entry')->nullable();
            $table->string('biometric_code', 50)->nullable();
            if (! in_array('tolerance_minutes', $columns)) {
                $table->unsignedInteger('tolerance_minutes')->default(15);
            }
            if (! in_array('allow_extra_hours', $columns)) {
                $table->boolean('allow_extra_hours')->default(true);
            }
            $table->unique(['store_id', 'biometric_code']);
            $table->index(['store_id', 'status']);
            $table->index(['store_id', 'document_number']);
        });
        foreach ($employees as $employee) {
            $user = DB::table('users')->find($employee->user_id);
            $roles = DB::table('model_has_roles')->join('roles', 'roles.id', '=', 'model_has_roles.role_id')
                ->where('model_has_roles.model_type', App\Models\User::class)
                ->where('model_has_roles.model_id', $user->id)->pluck('roles.name')->all();
            DB::table('employees')->where('id', $employee->id)->update([
                'store_id' => $owners[$employee->id],
                'name' => $user->name,
                'email' => $user->email,
                'phone' => $employee->phone ?: $user->phone,
                'identity_id' => $user->identity_id,
                'document_number' => $user->document_number,
                'status' => $user->status,
                'roles' => json_encode($roles, JSON_THROW_ON_ERROR),
                'date_entry' => null,
                'tolerance_minutes' => $employee->tolerance_minutes ?? $employee->tolerance ?? 15,
                // Transfer short clock codes from the previous CSV importer.
                'biometric_code' => [2 => '2', 3 => '1', 6 => '5'][$employee->id] ?? null,
            ]);
        }
        $foreign = collect(Schema::getForeignKeys('employees'))->first(fn ($key) => $key['columns'] === ['user_id']);
        if ($foreign) {
            Schema::table('employees', fn (Blueprint $table) => $table->dropForeign(
                DB::getDriverName() === 'sqlite' ? ['user_id'] : $foreign['name']
            ));
        }
        Schema::table('employees', function (Blueprint $table) {
            $table->unsignedBigInteger('user_id')->nullable()->change();
            $table->unsignedBigInteger('store_id')->nullable(false)->change();
            $table->string('name')->nullable(false)->change();
            $table->foreign('user_id')->references('id')->on('users')->nullOnDelete();
        });
        if (! Schema::hasColumn('attendances', 'comment')) {
            Schema::table('attendances', fn (Blueprint $table) => $table->string('comment', 250)->nullable());
        }
    }

    public function down(): void
    {
        throw new RuntimeException('Restaura el respaldo previo para revertir la independencia de empleados sin perder datos.');
    }
};
