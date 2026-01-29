<?php

namespace App\Http\Controllers\Api\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\Address;
use App\Models\store;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AddressDashboardController extends Controller
{
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
                'name'            => 'required|string|max:255',
                'phone'           => 'required',
                'document_number' => 'nullable|string|max:11',
                'primary'            => 'required|string|max:255',
                'secondary'            => 'nullable|string|max:255',
                'references'  => 'nullable|string|max:255',
                'district_id' => 'required',
                'user_id' => 'required|max:11',
            ]);

            $user = User::findOrFail($request->user_id);

            // 1. CREAR USUARIO
            $address = $user->addresses()->create([
                'name'            => $validated['name'],
                'phone'           => $validated['phone'],
                'document_number' => $validated['document_number'],
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

            return responseError($th, "Error al crear la direccion.... ");
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
                'name'             => 'required|string|max:255',
                'phone'            => 'required',
                'document_number'  => 'nullable|string|max:11',
                'primary'          => 'required|string|max:255',
                'secondary'        => 'nullable|string|max:255',
                'references'       => 'nullable|string|max:255',
                'district_id'      => 'required',
                'user_id'          => 'required|integer',
            ]);

            // 1. Usuario dueño
            $user = $store->users()
                ->where('user_id', $validated['user_id'])
                ->firstOrFail();

            // 2. Dirección que pertenece al usuario (esto garantiza que la direccion es del usuario en cuestion)
            // $address = Address::findOrFail($validated['address_id']); es peligroso porque no asegura la consistencia de los datos

            $address = $user->addresses()
                ->where('id', $address_id)
                ->firstOrFail();

            // 3. Update
            $address->update([
                'name'            => $validated['name'],
                'phone'           => $validated['phone'],
                'document_number' => $validated['document_number'],
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

            return responseError($th, "Error al actualizar la dirección");
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
