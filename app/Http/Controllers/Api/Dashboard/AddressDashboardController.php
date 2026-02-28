<?php

namespace App\Http\Controllers\Api\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\Address;
use App\Models\store;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class AddressDashboardController extends Controller
{

    protected function getParentModel(array $validated): Model
    {
        $map = [
            'customer' => \App\Models\Customer::class,
            'supplier' => \App\Models\Supplier::class,
            'employee' => \App\Models\Employee::class,
            // 'order' => \App\Models\Order::class,
            // 'purchase' => \App\Models\Purchase::class,
        ];

        if (! isset($map[$validated['addressable_type']])) { //ojo addressable_type viene del request (angular), no es un campo de la tabla addresses
            throw ValidationException::withMessages([
                'addressable_type' => 'Tipo de modelo no válido',
            ]);
        }

        return $map[$validated['addressable_type']]::findOrFail($validated['addressable_id']);
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
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
                'addressable_type' => [
                    'required',
                    Rule::in(['customer', 'supplier', 'employee']) // Agrega más tipos según sea necesario
                ],
                'addressable_id' => 'required|integer',
                'name'            => 'required|string|max:255',
                'phone'           => 'required',
                'document_number' => 'nullable|string|max:11',
                'identity_id'       => 'nullable|integer|max:11',
                'primary'            => 'required|string|max:255',
                'secondary'            => 'nullable|string|max:255',
                'references'  => 'nullable|string|max:255',
                'district_id' => 'required',
                'user_id' => 'required|max:11',
            ]);

            $parentModel = $this->getParentModel($validated); // Esto valida que el modelo padre exista, si no existe lanza una excepción

            $address = $parentModel->addresses()->create([
                'name' => $validated['name'],
                'phone' => $validated['phone'],
                'document_number' => $validated['document_number'],
                'identity_id' => $validated['identity_id'],
                'primary' => $validated['primary'],
                'secondary' => $validated['secondary'],
                'references' => $validated['references'],
                'district_id' => $validated['district_id'],
            ]);

            DB::commit();

            return responseOk($address->load('district.province.department'), "Se ha procesado correctamente la creacion del courier");

        } catch (\Throwable $th) {

            Log::info($th);

            DB::rollback();

            return responseError("Error al crear la direccion.... ");
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(store $store)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(store $store)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, store $store, $address_id)
    {
        //
        //
        try {

            DB::beginTransaction();

            $validated = $request->validate([

                'addressable_type' => [
                    'required',
                    Rule::in(['customer', 'supplier', 'employee']) // Agrega más tipos según sea necesario
                ],
                'addressable_id' => 'required|integer',
                'name'             => 'required|string|max:255',
                'phone'            => 'required',
                'document_number'  => 'nullable|string|max:11',
                'identity_id'      => 'nullable|integer|max:11',
                'primary'          => 'required|string|max:255',
                'secondary'        => 'nullable|string|max:255',
                'references'       => 'nullable|string|max:255',
                'district_id'      => 'required'

            ]);

            $parentModel = $this->getParentModel($validated);

            $address = $parentModel->addresses()->findOrFail($address_id); // Esto valida que la dirección exista y que pertenezca al modelo padre, si no existe lanza una excepción

            // 2. Dirección que pertenece al usuario (esto garantiza que la direccion es del usuario en cuestion)
            // $address = Address::findOrFail($validated['address_id']); es peligroso porque no asegura la consistencia de los datos

            // 3. Update
            $address->update([
                'name'            => $validated['name'],
                'phone'           => $validated['phone'],
                'document_number' => $validated['document_number'],
                'identity_id'       => $validated['identity_id'],
                'primary'         => $validated['primary'],
                'secondary'       => $validated['secondary'],
                'references'      => $validated['references'],
                'district_id'     => $validated['district_id'],
            ]);

            DB::commit();

            return responseOk($address->load('district.province.department'), "Se ha actualizado correctamente la dirección");
        } catch (\Throwable $th) {

            DB::rollback();

            Log::error($th);

            return responseError("Error al actualizar la dirección");
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(store $store)
    {
        //
    }
}
