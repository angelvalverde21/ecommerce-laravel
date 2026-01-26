<?php

namespace App\Http\Controllers\Api\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\Store;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SupplierDashboardController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        try {

            $suppliers = User::role('supplier')->get();

            return responseOk($suppliers, 'Suppliers obtenidos correctamente');
        } catch (\Throwable $th) {
            Log::info($th);
            return responseError($th, 'Ha ocurrido un error interno al obtener los suppliers');
        }
    }

    public function search(Store $store, $search = "")
    {

        if (trim($search) === '') {
            return $this->index();
        }

        try {
            $suppliers = User::role('supplier')
                ->where(function ($query) use ($search) {
                    $query->where('name', 'LIKE', '%' . $search . '%')
                        ->orWhere('email', 'LIKE', '%' . $search . '%')
                        ->orWhere('phone', 'LIKE', '%' . $search . '%')
                        ->orWhere('document_number', 'LIKE', '%' . $search . '%');
                })
                ->limit(10)
                ->get();

            return responseOk($suppliers, 'Suppliers obtenidos correctamente (search)');
        } catch (\Throwable $th) {
            Log::info($th);
            return responseError($th, 'Ha ocurrido un error interno al buscar los suppliers');
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
                // 'email'           => 'required|email|unique:users,email',
                'email'           => '',
                // 'phone'           => 'nullable|string|max:20',
                'phone'           => '',
                'document_number' => 'nullable|string|max:20',
            ]);

            DB::beginTransaction();

            $user = User::create([
                'name'            => $validated['name'],
                'email'           => $validated['email'],
                'phone'           => $validated['phone'],
                'document_number' => $validated['document_number'],
                'identity_id'     => 1,
                'password'        => bcrypt('123456'),
            ]);

            $user->syncRoles(['supplier']);

            DB::commit();

            return responseOk($user->load('roles'), 'Supplier creado correctamente', 201);

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
            $user = User::with(['roles'])->findOrFail($supplier_id);

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
