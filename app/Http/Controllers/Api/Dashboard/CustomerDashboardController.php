<?php

namespace App\Http\Controllers\Api\Dashboard;

use App\Http\Controllers\Controller;
use App\Http\Resources\FlatUserResource;
use App\Models\Customer;
use App\Models\Store;
use App\Models\User;
use App\Services\Dashboard\UserRelatedService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CustomerDashboardController extends Controller
{
    /**
     * Display a listing of the resource.
     */

    protected UserRelatedService $service;
    protected $name;

    public function __construct()
    {
        // Pasamos el modelo que vamos a usar
        $this->service = new UserRelatedService(Customer::class);
        $this->name = 'Customers';
    }

    public function index(Store $store)
    {
        try {
            return respondePaginateOk($this->service->index($store, 25), $this->name . ' obtenidos correctamente');
        } catch (\Throwable $th) {
            Log::error($th);
            return responseError('Error al obtener ' . $this->name);
        }
    }

    public function search(Store $store, $search)
    {

        try {

            //Esto quiere decir que si search viene vacio o con espacios en blanco, se llama al index, en caso contrario prosigo y llamo al search

            return respondePaginateOk(trim($search) ? $this->service->search($store, $search, 100) : $this->index($store), $this->name . ' obtenidos correctamente (search)');
        
        } catch (\Throwable $th) {

            Log::error($th);
            
            return responseError('Error al buscar ' . $this->name);

        }
    }

    public function blocked()
    {
        try {
            $customers = Customer::with('user') //entity_id=1 quiere decir para los usuarios registrados con dni
                ->where('status', 0) //solo bloqueados
                ->get();

            return responseOk($customers, "Clientes bloqueados obtenidos correctamente");
        } catch (\Throwable $th) {
            Log::info($th);
            return responseError("Ha ocurrido un error interno al obtener los clientes bloqueados");
        }
    }


    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Store $store, Request $request)
    {
        //

        try {

            // VALIDACIÓN
            $validated = $request->validate([
                'name'            => 'required|string|max:255',
                'email'           => 'required|email|unique:users,email',
                'phone'           => 'nullable|string|max:20',
                'document_number' => 'nullable|string|max:20',
            ]);

            DB::beginTransaction();

            // 1. CREAR USUARIO
            $user = $store->users()->create([
                'name'            => $validated['name'],
                'email'           => $validated['email'],
                'phone'           => $validated['phone'],
                'document_number' => $validated['document_number'],
                'identity_id' => $validated['identity_id'],
                'password'        => bcrypt($validated['document_number']),
            ]);

            $customer = $user->customer()->create();

            DB::commit();

            return responseOk($customer, "Customer creado correctamente", 201);
        } catch (\Throwable $th) {

            Log::info($th);

            DB::rollback();

            return responseError("Error al crear el customer.... ");
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Store $store, $customer_id)
    {
        try {
            // Obtener usuario con employee y roles
            $user = User::with(['roles'])->findOrFail($customer_id);

            // --- ELIMINAR RELACIÓN ORIGINAL ---
            $rolesOnlyNames = $user->roles->pluck('name');

            unset($user->roles);             // quita la relación de Eloquent
            $user->setRelation('roles', $rolesOnlyNames); // asigna array limpio

            return responseOk($user, "Customer obtenido correctamente");
        } catch (\Throwable $th) {
            return responseError("No se pudo obtener el Customer solicitado");
        }
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Store $store)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Store $store, $customer_id)
    {
        try {

            $user = User::findOrFail($customer_id);

            // ⭐ Validación flexible (parcial o completa)
            $validated = $request->validate([
                'name'            => 'sometimes|required|string|max:255',
                'email'           => "sometimes|required|email|unique:users,email,$customer_id",
                'phone'           => 'sometimes|nullable|string|max:20',
                'document_number' => 'sometimes|nullable|string|max:20',
                'roles'           => 'sometimes|required|array|min:1',
                'roles.*'         => 'string|exists:roles,name',
                'status'          => 'sometimes|required|in:0,1',
            ]);

            DB::beginTransaction();

            // 1. Actualizar USER (solo campos presentes)
            $user->update($validated);

            // 2. Sincronizar roles solo si llegan
            if ($request->has('roles')) {
                $user->syncRoles($validated['roles']);
            }

            DB::commit();

            return responseOk(
                $user->load('roles'),
                "Cliente actualizado correctamente",
                200
            );
        } catch (\Throwable $th) {

            DB::rollback();
            Log::info($th);

            return responseError("Error al actualizar el cliente...");
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Store $store)
    {
        //
    }
}
