<?php

namespace App\Http\Controllers\Api\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\Supplier;
use App\Models\Store;
use App\Models\User;
use App\Services\Dashboard\Supplier\SupplierService;
use App\Services\Dashboard\Supplier\FlatSupplierUserResource;
use App\Services\Dashboard\UserRelatedService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;

class SupplierDashboardController extends Controller
{


    protected SupplierService $supplierService;

    public function __construct()
    {
        // Pasamos el modelo que vamos a usar
        $this->supplierService = new SupplierService();
    }

    public function index(Store $store)
    {
        try {

            //Aqui ya service ya tiene el modelo que le hemos pasado, en este caso Supplier
            return responsePaginateOk($this->supplierService->index($store, 25), 'Suppliers activos obtenidos correctamente');
        } catch (\Throwable $th) {
            Log::error($th);
            return responseError('Error al obtener ' . 'Suppliers activos');
        }
    }

    public function active(Store $store)
    {
        try {
            //Aqui ya service ya tiene el modelo que le hemos pasado, en este caso Supplier
            return responsePaginateOk($this->supplierService->active($store, 25), 'Supplier activos obtenidos correctamente');
        } catch (\Throwable $th) {
            Log::error($th);
            return responseError('Error al obtener ' . 'Supplier activos');
        }
    }

    public function blocked(Store $store)
    {
        try {
            //Aqui ya service ya tiene el modelo que le hemos pasado, en este caso Supplier
            return responsePaginateOk($this->supplierService->blocked($store, 25), 'Supplier bloqueados obtenidos correctamente');
        } catch (\Throwable $th) {
            Log::error($th);
            return responseError('Error al obtener ' . 'Supplier bloqueados');
        }
    }

    public function search(Store $store, string $search = '')
    {
        try {

            $search = trim($search);

            if ($search === '') {
                $suppliers = $this->supplierService->index($store, 100);
            } else {
                $suppliers = $this->supplierService->search($store, $search, 100);
            }

            return responsePaginateOk(
                $suppliers,
                'Suppliers obtenidos correctamente'
            );
        } catch (\Throwable $th) {

            Log::error($th);

            return responseError(
                $th,
                'Error al buscar suppliers'
            );
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

            DB::beginTransaction();

            $validated = $request->validate([
                'name'            => 'required|string|max:255',
                'email'           => 'nullable|email|unique:users,email',
                'phone'           => 'required|string|max:20',
                'document_number' => 'nullable|string|max:20',
                'identity_id'     => 'nullable|integer|exists:identities,id',
            ]);

            // 1. CREAR USUARIO
            $user = User::create([
                'name'            => $validated['name'],
                'email'           => $validated['email'],
                'phone'           => $validated['phone'],
                'document_number' => $validated['document_number'],
                'identity_id'     => $validated['identity_id'],
                'password'        => bcrypt($validated['document_number']),
            ]);

            // 2. ASIGNAR USUARIO A LA TIENDA
            $user->stores()->attach($store->id);

            // 3. CREAR Supplier
            $supplier = $user->supplier()->create(); //Crear el Supplier relacionado al user

            DB::commit();

            return responseOk(
                new FlatSupplierUserResource(
                    $supplier->fresh(['user'])
                ),
                "Se ha procesado correctamente la creacion del Supplier"
            );
        } catch (\Throwable $th) {

            Log::info($th);

            DB::rollback();

            return responseError("Error al crear el Supplier.... ");
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Store $store, $supplier_id)
    {

        try {
            //
            $supplier = Supplier::findOrFail($supplier_id);

            Log::info($supplier);

            //VALIDACIÓN FLEXIBLE (update parcial o completo)
            $validated = $request->validate([
                'name'            => 'sometimes|required|string|max:255',
                'email' => [
                    'nullable',
                    'email',
                    Rule::unique('users', 'email')->ignore($supplier->user->id),
                ],
                'phone'           => 'sometimes|nullable|string|max:20',
                'document_number' => 'sometimes|nullable|string|max:20',
                'status'          => 'sometimes|required|in:0,1',
                'is_cash_on_delivery' => 'sometimes|nullable|boolean',
                'is_freight_collect'  => 'sometimes|nullable|boolean',
                'is_express_shipping'  => 'sometimes|nullable|boolean',
            ]);

            DB::beginTransaction();

            // 1. ACTUALIZAR USER (solo lo que venga en el request)
            $supplier->user->update(
                [
                    'name'            => $validated['name'] ?? $supplier->user->name,
                    'email'           => $validated['email'] ?? $supplier->user->email,
                    'phone'           => $validated['phone'] ?? $supplier->user->phone,
                    'document_number' => $validated['document_number'] ?? $supplier->user->document_number,
                    'status'          => $validated['status'] ?? $supplier->user->status,
                ]
            );

            // 2. ACTUALIZAR Supplier
            // $supplier->update();

            DB::commit();

            return responseOk(
                new FlatSupplierUserResource(
                    $supplier->fresh(['user'])
                ),
                "Supplier actualizado correctamente",
                200
            );
        } catch (\Throwable $th) {

            DB::rollback();
            Log::info($th);

            return responseError("Error al actualizar el Supplier...");
        }
    }
    /**
     * Display the specified resource.
     */
    public function show(Store $store, $supplier_id)
    {
        try {
            //Aqui ya service ya tiene el modelo que le hemos pasado, en este caso Supplier
            return responseOk($this->supplierService->show($store, $supplier_id), 'Supplier obtenidos correctamente');
        } catch (\Throwable $th) {
            Log::error($th);
            return responseError('Error al obtener ' . $supplier_id);
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
     * Remove the specified resource from storage.
     */
    public function destroy(Store $store)
    {
        //
    }
}
