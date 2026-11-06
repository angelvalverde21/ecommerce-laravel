<?php

namespace App\Http\Controllers\Api\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\Address;
use App\Models\Store;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class AddressDashboardController extends Controller
{

    protected function getParentModel(array $validated, Store $store): Model
    {
        if ($validated['addressable_type'] === 'store') {
            return $store;
        }

        $map = [
            'customer' => \App\Models\Customer::class,
            'supplier' => \App\Models\Supplier::class,
            'employee' => \App\Models\Employee::class,
            'courier' => \App\Models\Courier::class,
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
                    Rule::in(['customer', 'supplier', 'employee', 'courier', 'store'])
                ],
                'addressable_id' => $request->input('addressable_type') === 'store'
                    ? ['nullable', 'integer', Rule::in([$store->id])]
                    : ['required', 'integer'],
                'name'            => 'required|string|max:255',
                'phone' => in_array($request->input('addressable_type'), ['courier', 'store'], true)
                    ? 'nullable|string|max:30'
                    : 'required',
                'document_number' => 'nullable|string|max:11',
                'identity_id'       => 'nullable|integer|max:11',
                'primary'            => 'required|string|max:255',
                'secondary'            => 'nullable|string|max:255',
                'references'  => 'nullable|string|max:255',
                'district_id' => 'required|integer|exists:districts,id',
            ]);

            $parentModel = $this->getParentModel($validated, $store);

            $address = $parentModel->addresses()->create([
                'name' => $validated['name'],
                'phone' => $validated['phone'] ?? null,
                'document_number' => $validated['document_number'] ?? null,
                'identity_id' => $validated['identity_id'] ?? null,
                'primary' => $validated['primary'],
                'secondary' => $validated['secondary'] ?? null,
                'references' => $validated['references'] ?? null,
                'district_id' => $validated['district_id'],
            ]);

            DB::commit();

            return responseOk($address->load('district.province.department'), 'Dirección creada correctamente.');

        } catch (\Illuminate\Validation\ValidationException | \Illuminate\Database\Eloquent\ModelNotFoundException $exception) {
            DB::rollBack();
            throw $exception;
        } catch (\Throwable $th) {

            Log::info($th);

            DB::rollback();

            return responseError("Error al crear la direccion.... ");
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Store $store, int $address_id)
    {
        return responseOk($store->addresses()->with('district.province.department')->findOrFail($address_id));
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
                    Rule::in(['customer', 'supplier', 'employee', 'courier', 'store'])
                ],
                'addressable_id' => $request->input('addressable_type') === 'store'
                    ? ['nullable', 'integer', Rule::in([$store->id])]
                    : ['required', 'integer'],
                'name'             => 'required|string|max:255',
                'phone' => in_array($request->input('addressable_type'), ['courier', 'store'], true)
                    ? 'nullable|string|max:30'
                    : 'required',
                'document_number'  => 'nullable|string|max:11',
                'identity_id'      => 'nullable|integer|max:11',
                'primary'          => 'required|string|max:255',
                'secondary'        => 'nullable|string|max:255',
                'references'       => 'nullable|string|max:255',
                'district_id' => 'required|integer|exists:districts,id',

            ]);

            $parentModel = $this->getParentModel($validated, $store);

            $address = $parentModel->addresses()->findOrFail($address_id); // Esto valida que la dirección exista y que pertenezca al modelo padre, si no existe lanza una excepción

            // 2. Dirección que pertenece al usuario (esto garantiza que la direccion es del usuario en cuestion)
            // $address = Address::findOrFail($validated['address_id']); es peligroso porque no asegura la consistencia de los datos

            // 3. Update
            $address->update([
                'name'            => $validated['name'],
                'phone'           => $validated['phone'] ?? null,
                'document_number' => $validated['document_number'] ?? null,
                'identity_id'       => $validated['identity_id'] ?? null,
                'primary'         => $validated['primary'],
                'secondary'       => $validated['secondary'] ?? null,
                'references'      => $validated['references'] ?? null,
                'district_id'     => $validated['district_id'],
            ]);

            DB::commit();

            return responseOk($address->load('district.province.department'), "Se ha actualizado correctamente la dirección");
        } catch (\Illuminate\Validation\ValidationException | \Illuminate\Database\Eloquent\ModelNotFoundException $exception) {
            DB::rollBack();
            throw $exception;
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
