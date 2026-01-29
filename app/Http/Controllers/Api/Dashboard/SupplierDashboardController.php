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
            return responseError($th, 'Error al obtener ' . 'Suppliers activos');
        }
    }

    public function active(Store $store)
    {
        try {
            //Aqui ya service ya tiene el modelo que le hemos pasado, en este caso Supplier
            return responsePaginateOk($this->supplierService->active($store, 25), 'Supplier activos obtenidos correctamente');
        } catch (\Throwable $th) {
            Log::error($th);
            return responseError($th, 'Error al obtener ' . 'Supplier activos');
        }
    }

    public function blocked(Store $store)
    {
        try {
            //Aqui ya service ya tiene el modelo que le hemos pasado, en este caso Supplier
            return responsePaginateOk($this->supplierService->blocked($store, 25), 'Supplier bloqueados obtenidos correctamente');
        } catch (\Throwable $th) {
            Log::error($th);
            return responseError($th, 'Error al obtener ' . 'Supplier bloqueados');
        }
    }

    public function search(Store $store, $search)
    {

        try {

            //Esto quiere decir que si search viene vacio o con espacios en blanco, se llama al index, en caso contrario prosigo y llamo al search

            $Suppliers = trim($search) ? $this->supplierService->search($store, $search, 100) : $this->index($store);
            return responsePaginateOk($Suppliers, 'Supplier obtenidos correctamente (search)');

        } catch (\Throwable $th) {

            Log::error($th);
            return responseError($th, 'Error al buscar ' . $search);
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
                'email'           => 'required|email|unique:users,email',
                'phone'           => 'nullable|string|max:20',
                'document_number' => 'nullable|string|max:20',
                'is_cash_on_delivery' => 'nullable|boolean',
                'is_freight_collect'  => 'nullable|boolean',
                'is_express_shipping'  => 'nullable|boolean',
            ]);

            // 1. CREAR USUARIO
            $user = User::create([
                'name'            => $validated['name'],
                'email'           => $validated['email'],
                'phone'           => $validated['phone'],
                'document_number' => $validated['document_number'],
                'identity_id'     => 2, //2 es para RUC,
                'password'        => bcrypt($validated['document_number']),
            ]);

            // 2. ASIGNAR USUARIO A LA TIENDA
            $user->stores()->attach($store->id);

            // 3. CREAR Supplier
            $Supplier = $user->supplier()->create([
                'is_cash_on_delivery' => $validated['is_cash_on_delivery'] ?? false,
                'is_freight_collect'  => $validated['is_freight_collect'] ?? false,
                'is_express_shipping'  => $validated['is_express_shipping'] ?? false,
            ]); //Crear el Supplier relacionado al user

            DB::commit();

            return responseOk( new FlatSupplierUserResource($Supplier->fresh(['user']) ),  "Se ha procesado correctamente la creacion del Supplier");
                    
        } catch (\Throwable $th) {

            Log::info($th);

            DB::rollback();

            return responseError($th, "Error al crear el Supplier.... ");
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Store $store, $supplier_id)
    {

        try {
            //Aquí ya service ya tiene el modelo que le hemos pasado, en este caso Supplier
            $Supplier = Supplier::findOrFail($supplier_id);

            Log::info($Supplier);

            // ⭐ VALIDACIÓN FLEXIBLE (update parcial o completo)
            $validated = $request->validate([
                'name'            => 'sometimes|required|string|max:255',
                'email' => [
                    'nullable',
                    'email',
                    Rule::unique('users', 'email')->ignore($Supplier->user->id),
                ],
                'phone'           => 'sometimes|nullable|string|max:20',
                'document_number' => 'sometimes|nullable|string|max:20',
                'status'          => 'sometimes|required|in:0,1',
                'is_cash_on_delivery' => 'sometimes|nullable|boolean',
                'is_freight_collect'  => 'sometimes|nullable|boolean',
                'is_express_shipping'  => 'sometimes|nullable|boolean',
            ]);

            DB::beginTransaction();

            // ⭐ 1. ACTUALIZAR USER (solo lo que venga en el request)
            $Supplier->user->update(
                [
                    'name'            => $validated['name'] ?? $Supplier->user->name,
                    'email'           => $validated['email'] ?? $Supplier->user->email,
                    'phone'           => $validated['phone'] ?? $Supplier->user->phone,
                    'document_number' => $validated['document_number'] ?? $Supplier->user->document_number,
                    'status'          => $validated['status'] ?? $Supplier->user->status,
                ]
            );

            // ⭐ 2. ACTUALIZAR Supplier
            $Supplier->update(
                [
                    'is_cash_on_delivery' => $validated['is_cash_on_delivery'] ?? $Supplier->is_cash_on_delivery,
                    'is_freight_collect'  => $validated['is_freight_collect'] ?? $Supplier->is_freight_collect,
                ]
            );

            DB::commit();

            return responseOk(
                new FlatSupplierUserResource(
                    $Supplier->fresh(['user'])
                ),
                "Supplier actualizado correctamente",
                200
            );
        } catch (\Throwable $th) {

            DB::rollback();
            Log::info($th);

            return responseError($th, "Error al actualizar el Supplier...");
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
            return responseError($th, 'Error al obtener ' . $supplier_id);
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
