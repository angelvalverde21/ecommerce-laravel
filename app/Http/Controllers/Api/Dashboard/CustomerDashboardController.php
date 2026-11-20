<?php

namespace App\Http\Controllers\Api\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\{Customer, Store, User};
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class CustomerDashboardController extends Controller
{
    private function customers(Store $store)
    {
        return Customer::query()->whereHas('user.stores', fn ($query) => $query->where('stores.id', $store->id));
    }

    // Customer URLs retain the existing user ID contract.
    private function customerUser(Store $store, int $userId): User
    {
        return $this->customers($store)->where('user_id', $userId)->with('user')->firstOrFail()->user;
    }

    public function index(Store $store)
    {
        return responseOk($this->customers($store)->with('user')->get(), 'Clientes obtenidos correctamente');
    }

    public function search(Store $store, string $search = '')
    {
        $term = trim($search);
        $customers = $this->customers($store)->with('user')
            ->when($term !== '', fn ($query) => $query->whereHas('user', fn ($user) => $user
                ->where(fn ($fields) => $fields->where('name', 'like', "%{$term}%")
                    ->orWhere('email', 'like', "%{$term}%")->orWhere('phone', 'like', "%{$term}%")
                    ->orWhere('document_number', 'like', "%{$term}%"))))
            ->get();
        return responseOk($customers, 'Clientes encontrados');
    }

    public function blocked(Store $store)
    {
        return responseOk($this->customers($store)->whereHas('user', fn ($query) => $query->where('status', 0))
            ->with('user')->get(), 'Clientes bloqueados');
    }

    public function store(Store $store, Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users,email',
            'phone' => 'nullable|string|max:20',
            'document_number' => 'nullable|string|max:20',
        ]);
        $customer = DB::transaction(function () use ($data, $store) {
            $user = User::create([
                'name' => $data['name'], 'email' => $data['email'],
                'phone' => $data['phone'] ?? null, 'document_number' => $data['document_number'] ?? null,
                'password' => bcrypt(Str::random(48)),
            ]);
            $user->stores()->attach($store->id);
            return $user->customer()->create()->load('user');
        });
        return responseOk($customer, 'Cliente creado correctamente', 201);
    }

    public function show(Store $store, int $customer_id)
    {
        $user = $this->customerUser($store, $customer_id)->load('roles');
        $roles = $user->roles->pluck('name');
        $user->unsetRelation('roles')->setRelation('roles', $roles);
        return responseOk($user, 'Cliente obtenido correctamente');
    }

    public function update(Request $request, Store $store, int $customer_id)
    {
        $user = $this->customerUser($store, $customer_id);
        $data = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'email' => ['sometimes', 'required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'phone' => 'sometimes|nullable|string|max:20',
            'document_number' => 'sometimes|nullable|string|max:20',
            'roles' => 'sometimes|required|array|min:1',
            'roles.*' => 'string|exists:roles,name',
            'status' => 'sometimes|required|in:0,1',
        ]);
        DB::transaction(function () use ($user, $data) {
            $user->update(collect($data)->except('roles')->all());
            if (array_key_exists('roles', $data)) $user->syncRoles($data['roles']);
        });
        return responseOk($user->fresh()->load('roles'), 'Cliente actualizado correctamente');
    }
}
