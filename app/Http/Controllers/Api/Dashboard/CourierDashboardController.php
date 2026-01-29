<?php

namespace App\Http\Controllers\Api\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\Courier;
use App\Models\Store;
use App\Models\User;
use App\Services\Dashboard\Courier\CourierService;
use App\Services\Dashboard\Courier\FlatCourierUserResource;
use App\Services\Dashboard\UserRelatedService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;

class CourierDashboardController extends Controller
{


    protected CourierService $courierService;

    public function __construct()
    {
        // Pasamos el modelo que vamos a usar
        $this->courierService = new CourierService();
    }

    public function index(Store $store)
    {
        try {

            //Aqui ya service ya tiene el modelo que le hemos pasado, en este caso Courier
            return responsePaginateOk($this->courierService->index($store, 25), 'Couriers activos obtenidos correctamente');

        } catch (\Throwable $th) {
            Log::error($th);
            return responseError($th, 'Error al obtener ' . 'Couriers activos');
        }
    }

    public function active(Store $store)
    {
        try {
            //Aqui ya service ya tiene el modelo que le hemos pasado, en este caso Courier
            return responsePaginateOk($this->courierService->active($store, 25), 'Courier activos obtenidos correctamente');
        } catch (\Throwable $th) {
            Log::error($th);
            return responseError($th, 'Error al obtener ' . 'Courier activos');
        }
    }

    public function blocked(Store $store)
    {
        try {
            //Aqui ya service ya tiene el modelo que le hemos pasado, en este caso Courier
            return responsePaginateOk($this->courierService->blocked($store, 25), 'Courier bloqueados obtenidos correctamente');
        } catch (\Throwable $th) {
            Log::error($th);
            return responseError($th, 'Error al obtener ' . 'Courier bloqueados');
        }
    }

    public function search(Store $store, $search)
    {

        try {

            //Esto quiere decir que si search viene vacio o con espacios en blanco, se llama al index, en caso contrario prosigo y llamo al search

            $couriers = trim($search) ? $this->courierService->search($store, $search, 100) : $this->index($store);
            return responsePaginateOk($couriers, 'Courier obtenidos correctamente (search)');

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

            // 3. CREAR COURIER
            $courier = $user->courier()->create([
                'is_cash_on_delivery' => $validated['is_cash_on_delivery'] ?? false,
                'is_freight_collect'  => $validated['is_freight_collect'] ?? false,
                'is_express_shipping'  => $validated['is_express_shipping'] ?? false,
            ]); //Crear el courier relacionado al user

            DB::commit();

            return responseOk(
                    new FlatCourierUserResource(
                        $courier->fresh(['user'])
                    ), 
                    "Se ha procesado correctamente la creacion del courier");
                    
        } catch (\Throwable $th) {

            Log::info($th);

            DB::rollback();

            return responseError($th, "Error al crear el courier.... ");
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Store $store, $courier_id)
    {

        try {
            //
            $courier = Courier::findOrFail($courier_id);

            Log::info($courier);

            // ⭐ VALIDACIÓN FLEXIBLE (update parcial o completo)
            $validated = $request->validate([
                'name'            => 'sometimes|required|string|max:255',
                'email' => [
                    'nullable',
                    'email',
                    Rule::unique('users', 'email')->ignore($courier->user->id),
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
            $courier->user->update(
                [
                    'name'            => $validated['name'] ?? $courier->user->name,
                    'email'           => $validated['email'] ?? $courier->user->email,
                    'phone'           => $validated['phone'] ?? $courier->user->phone,
                    'document_number' => $validated['document_number'] ?? $courier->user->document_number,
                    'status'          => $validated['status'] ?? $courier->user->status,
                ]
            );

            // ⭐ 2. ACTUALIZAR COURIER
            $courier->update(
                [
                    'is_cash_on_delivery' => $validated['is_cash_on_delivery'] ?? $courier->is_cash_on_delivery,
                    'is_freight_collect'  => $validated['is_freight_collect'] ?? $courier->is_freight_collect,
                ]
            );

            DB::commit();

            return responseOk(
                new FlatCourierUserResource(
                    $courier->fresh(['user'])
                ),
                "Courier actualizado correctamente",
                200
            );
        } catch (\Throwable $th) {

            DB::rollback();
            Log::info($th);

            return responseError($th, "Error al actualizar el courier...");
        }
    }
    /**
     * Display the specified resource.
     */
    public function show(Store $store, $courier_id)
    {
        try {
            //Aqui ya service ya tiene el modelo que le hemos pasado, en este caso Courier
            return responseOk($this->courierService->show($store, $courier_id), 'Courier obtenidos correctamente');
        } catch (\Throwable $th) {
            Log::error($th);
            return responseError($th, 'Error al obtener ' . $courier_id);
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
