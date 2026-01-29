<?php

namespace App\Http\Controllers\Api\Dashboard;

use App\Http\Controllers\Controller;
use App\Http\Resources\FlatUserResource;
use App\Http\Resources\SupplierResource;
use App\Models\Store;
use App\Models\Supplier;
use App\Models\User;
use App\Services\UserRelatedService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SupplierDashboardController extends Controller
{
    /**
     * Display a listing of the resource.
     */

    protected UserRelatedService $service;
    protected $name;

    public function __construct()
    {
        // Pasamos el modelo que vamos a usar
        $this->service = new UserRelatedService(Supplier::class);
        $this->name = 'Suppliers';
    }

    public function index(Store $store)
    {
        try {
            //Aqui ya service ya tiene el modelo que le hemos pasado, en este caso Supplier
            return respondePaginateOk($this->service->index($store, 25), $this->name . ' obtenidos correctamente');

        } catch (\Throwable $th) {
            Log::error($th);
            return responseError($th, 'Error al obtener ' . $this->name);
        }
    }

    public function search(Store $store, $search)
    {

        try {

            // if (trim($search) === '') {
            //     $suppliers = $this->index($store);
            // } else {
            //     $suppliers = $this->service->search($store, $search, 100);
            // }

            //Esto quiere decir que si search viene vacio o con espacios en blanco, se llama al index, en caso contrario prosigo y llamo al search

            return respondePaginateOk(trim($search) ? $this->service->search($store, $search, 100) : $this->index($store), $this->name . ' obtenidos correctamente (search)');

        } catch (\Throwable $th) {

            Log::error($th);
            return responseError($th, 'Error al buscar ' . $this->name);

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
        try {

            $validated = $request->validate([
                'name'            => 'required|string|max:255',
                'email'           => 'nullable|email|unique:users,email',
                'phone'           => 'nullable',
                'document_number' => 'nullable|string|max:20',
                'identity_id'     => 'nullable|string|max:20',
            ]);

            DB::beginTransaction();

            $user = $store->users()->create([
                'name'            => $validated['name'],
                'email'           => $validated['email'],
                'phone'           => $validated['phone'],
                'document_number' => $validated['document_number'],
                'identity_id'     => $validated['identity_id'],
                'password'        => bcrypt($validated['document_number']),
            ]);

            $supplier = $user->supplier()->create();

            DB::commit();

            return responseOk($supplier, 'Supplier creado correctamente', 201);

        } catch (\Throwable $th) {
            Log::info($th);
            DB::rollBack();

            return responseError($th, 'Error al crear el supplier');
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Store $store, $supplier_id)
    {
        try {
            $user = User::with(['supplier'])->findOrFail($supplier_id);


            $rolesOnlyNames = $user->roles->pluck('name');

            unset($user->roles);

            $user->setRelation('roles', $rolesOnlyNames);

            return responseOk($user, 'Supplier obtenido correctamente');
        } catch (\Throwable $th) {
            return responseError($th, 'No se pudo obtener el supplier solicitado');
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
    public function update(Request $request, Store $store, $supplier_id)
    {
        try {

            $user = User::findOrFail($supplier_id);

            // ⭐ VALIDACIÓN FLEXIBLE (update parcial o completo)
            $validated = $request->validate([
                'name'            => 'sometimes|required|string|max:255',
                'email'           => "sometimes|required|email|unique:users,email,$supplier_id",
                'phone'           => 'sometimes|nullable|string|max:20',
                'document_number' => 'sometimes|nullable|string|max:20',
                'roles'           => 'sometimes|required|array|min:1',
                'roles.*'         => 'string|exists:roles,name',
                'status'          => 'sometimes|required|in:0,1',
            ]);

            DB::beginTransaction();

            // ⭐ 1. ACTUALIZAR USER (solo lo que venga en el request)
            $user->update($validated);

            // ⭐ 2. Actualizar roles SOLO si el request los incluye
            if ($request->has('roles')) {
                $user->syncRoles($validated['roles']);
            }

            DB::commit();

            return responseOk(
                $user->load('roles'),
                "Proveedor actualizado correctamente",
                200
            );
            
        } catch (\Throwable $th) {

            DB::rollback();
            Log::info($th);

            return responseError($th, "Error al actualizar el proveedor...");
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
