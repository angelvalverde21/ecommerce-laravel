<?php

namespace App\Http\Controllers\Api\Dashboard;

use App\Http\Controllers\Controller;
use App\Http\Resources\FlatUserResource;
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
    public function index(Store $store)
    {
        //
        try {

            $employees = $store->employees()->with('user')->get();
            
            // $employees = FlatUserResource::collection( //FlatUserResource aplana los datos de usuario para evitar usar supplier->user en el front sino todo plano
            //     $store->employees()->get()
            // );

            return responseOk($employees, 'Employees obtenidos correctamente');
        } catch (\Throwable $th) {
            //throw $th;
            Log::info($th);
            return responseError("Ha ocurrido un error interno al obtener los datos de los empleados");
        }
    }

    public function search(Store $store, $search = "")
    {

        if (trim($search) === '' || $search === null) {
            return $this->index($store);
        }

        try {

            $employees = Employee::whereHas('user', function ($query) use ($search) {
                $query->where('name', 'LIKE', "%{$search}%")
                    ->orWhere('email', 'LIKE', "%{$search}%")
                    ->orWhere('phone', 'LIKE', "%{$search}%")
                    ->orWhere('document_number', 'LIKE', "%{$search}%");
            })
                ->with('user') // anidado
                ->limit(10)
                ->get();

            return responseOk($employees, "Empleados obtenidos correctamente (search)");
        } catch (\Throwable $th) {
            Log::info($th);
            return responseError("Ha ocurrido un error interno al buscar los empleados");
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
            $user = $store->users()->create([
                'name'            => $validated['name'],
                'email'           => $validated['email'],
                'phone'           => $validated['phone'],
                'document_number' => $validated['document_number'],
                'identity_id'     => $validated['identity_id'],
                'password'        => bcrypt($validated['document_number']),
            ]);

            // 2. CREAR EMPLEADO ASOCIADO

            $employee = $user->employee()->create(
                [
                    'salary'     => $validated['salary'] ?? null,
                    'date_entry' => now(), // FECHA DE CREACIÓN DEL REGISTRO
                ]
            );


            // 3. ASIGNAR ROLES (SPATIE), osea si se ha mandado roles se les debe asignar aqui
            $user->syncRoles($validated['roles']); //se agrega el rol del futuro empleado

            DB::commit();

            return responseOk($employee, "Empleado creado correctamente", 201);
        } catch (\Throwable $th) {

            Log::info($th);

            DB::rollback();

            return responseError("Error al crear el empleado.... ");
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
            return responseError("No se pudo obtener el empleado solicitado");
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

            $user = User::findOrFail($employed_id);

            // ⭐ VALIDACIÓN FLEXIBLE (update parcial o completo)
            $validated = $request->validate([
                'name'            => 'sometimes|required|string|max:255',
                'email'           => "sometimes|required|email|unique:users,email,$employed_id",
                'phone'           => 'sometimes|nullable|string|max:20',
                'document_number' => 'sometimes|nullable|string|max:20',
                'salary'          => 'sometimes|nullable|numeric|min:0',
                'roles'           => 'sometimes|required|array|min:1',
                'roles.*'         => 'string|exists:roles,name',
                'status'          => 'sometimes|required|in:0,1',
            ]);

            DB::beginTransaction();

            // ⭐ 1. Actualizar USER (solo campos presentes)
            $user->update($validated);

            // ⭐ 2. Actualizar EMPLOYEE si llega salary
            if ($request->has('salary')) {
                $employee = $user->employee;

                if (!$employee) {
                    // Si aún no existe relación employee
                    $employee = Employee::create([
                        'user_id'    => $user->id,
                        'salary'     => $validated['salary'] ?? null,
                        'date_entry' => now(),
                    ]);
                } else {
                    $employee->update([
                        'salary' => $validated['salary'] ?? null
                    ]);
                }
            }

            // ⭐ 3. Actualizar ROLES si llegan
            if ($request->has('roles')) {
                $user->syncRoles($validated['roles']);
            }

            DB::commit();

            return responseOk(
                $user->load('roles', 'employee'),
                "Empleado actualizado correctamente",
                200
            );
        } catch (\Throwable $th) {

            DB::rollback();
            Log::info($th);

            return responseError("Error al actualizar el empleado...");
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
