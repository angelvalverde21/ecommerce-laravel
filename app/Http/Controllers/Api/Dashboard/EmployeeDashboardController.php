<?php

namespace App\Http\Controllers\Api\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\Store;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Spatie\Permission\Models\Role;

class EmployeeDashboardController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
        try {
            $employees = User::whereHas('employee')->with('employee')->get(); //Busca los usuarios que esten en la tabla empleados

            return responseOk($employees, "Empleados obtenidos correctamente");
        } catch (\Throwable $th) {
            //throw $th;
            return responseError($th, "Ha ocurrido un error interno al obtener los datos de los empleados");
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
                'salary'          => 'nullable|numeric|min:0',
                'roles'           => 'required|array|min:1',
                'roles.*'         => 'string|exists:roles,name',
            ]);

            DB::beginTransaction();

            // 1. CREAR USUARIO
            $user = User::create([
                'name'            => $validated['name'],
                'email'           => $validated['email'],
                'phone'           => $validated['phone'],
                'document_number' => $validated['document_number'],
                'identity_id' => 1,
                'password'        => bcrypt('123456'),
            ]);

            // 2. CREAR EMPLEADO ASOCIADO
            Employee::create([
                'user_id'    => $user->id,
                'salary'     => $validated['salary'] ?? null,
                'date_entry' => now(), // FECHA DE CREACIÓN DEL REGISTRO
            ]);

            // 3. ASIGNAR ROLES (SPATIE)
            $user->syncRoles($validated['roles']);

            DB::commit();

            return responseOk($user->load('roles'), "Empleado creado correctamente", 201);
        } catch (\Throwable $th) {

            Log::info($th);

            DB::rollback();

            return responseError($th, "Error al crear el empleado.... ");
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Store $store, $employed_id)
    {
        try {
            // Obtener usuario con employee y roles
            $user = User::whereHas('employee')
                ->with(['employee', 'roles'])
                ->findOrFail($employed_id);

            // --- ELIMINAR RELACIÓN ORIGINAL ---
            $rolesOnlyNames = $user->roles->pluck('name');

            unset($user->roles);             // quita la relación de Eloquent
            $user->setRelation('roles', $rolesOnlyNames); // asigna array limpio

            return responseOk($user, "Empleado obtenido correctamente");
        } catch (\Throwable $th) {
            return responseError($th, "No se pudo obtener el empleado solicitado");
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
    public function update(Store $store, Request $request, $employed_id)
    {
        try {

            // VALIDACIÓN
            $validated = $request->validate([
                'name'            => 'required|string|max:255',
                'email'           => "required|email|unique:users,email,$employed_id",
                'phone'           => 'nullable|string|max:20',
                'document_number' => 'nullable|string|max:20',
                'salary'          => 'nullable|numeric|min:0',
                'roles'           => 'required|array|min:1',
                'roles.*'         => 'string|exists:roles,name',
            ]);

            DB::beginTransaction();

            // 1. BUSCAR USER
            $user = User::findOrFail($employed_id);

            // 2. ACTUALIZAR USER
            $user->update([
                'name'            => $validated['name'],
                'email'           => $validated['email'],
                'phone'           => $validated['phone'],
                'document_number' => $validated['document_number'],
                // 'identity_id'     => 1, // si tus usuarios siempre tienen identity_id=1
            ]);

            // 3. ACTUALIZAR EMPLEADO
            $employee = $user->employee; // relación 1:1

            if (!$employee) {
                // por si no existe aún
                $employee = Employee::create([
                    'user_id'    => $user->id,
                    'salary'     => $validated['salary'] ?? null,
                    'date_entry' => now(),
                ]);
            } else {
                $employee->update([
                    'salary' => $validated['salary'] ?? null,
                ]);
            }

            // 4. SINCRONIZAR ROLES
            $user->syncRoles($validated['roles']);

            DB::commit();

            return responseOk(
                $user->load('roles', 'employee'),
                "Empleado actualizado correctamente",
                200
            );
        } catch (\Throwable $th) {

            DB::rollback();
            Log::info($th);

            return responseError($th, "Error al actualizar el empleado...");
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
