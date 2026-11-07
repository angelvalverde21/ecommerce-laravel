<?php

namespace App\Services\Dashboard\Employee;

use App\Models\Employee;
use App\Models\Store;
use App\Models\User;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

class EmployeeAccountService
{
    public function save(Store $store, array $values, ?Employee $employee = null): Employee
    {
        return DB::transaction(function () use ($store, $values, $employee) {
            $user = $employee?->user()->lockForUpdate()->first();
            if (! $user) {
                $user = new User;
                $user->name = $values['name'];
                $user->email = $values['email'];
                $user->password = $values['password'];
                $user->status = $values['status'] ?? true;
            } else {
                $account = ['name' => $values['name']];
                if (array_key_exists('email', $values) && $values['email'] !== $user->email) {
                    $account['email'] = $values['email'];
                    $account['email_verified_at'] = null;
                }
                if (filled($values['password'] ?? null)) {
                    $account['password'] = $values['password'];
                }
                if (array_key_exists('status', $values)) {
                    $account['status'] = $values['status'];
                }
                foreach ($account as $field => $value) {
                    $user->{$field} = $value;
                }
            }
            foreach (['phone', 'identity_id', 'document_number'] as $field) {
                if (array_key_exists($field, $values)) {
                    $user->{$field} = $values[$field];
                }
            }
            $user->save();
            $user->stores()->syncWithoutDetaching([$store->id]);
            if (array_key_exists('roles', $values)) {
                $user->syncRoles($values['roles']);
            }
            $data = Arr::except($values, [
                'name', 'email', 'account_email', 'phone', 'identity_id', 'document_number',
                'roles', 'password', 'password_confirmation', 'user_id',
            ]);
            if ($employee) {
                $employee->update([...$data, 'user_id' => $user->id]);
            } else {
                $employee = $store->employees()->create([
                    ...$data,
                    'user_id' => $user->id,
                    'date_entry' => $values['date_entry'] ?? now()->toDateString(),
                ]);
            }

            return $employee->fresh();
        });
    }

    public function present(Employee $employee): Employee
    {
        $employee->loadMissing('user.roles');
        $user = $employee->user;
        $employee->setAttribute('account', $user ? $user->only(['id', 'email', 'status']) : null);
        $employee->makeHidden('user');

        return $employee;
    }
}
