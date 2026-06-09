<?php

namespace App\Http\Controllers\Api\Dashboard;

use App\Http\Controllers\Controller;
use App\Http\Requests\SearchRequest;
use App\Http\Resources\FlatUserResource;
use App\Models\Employee;
use App\Models\Payment;
use App\Models\Store;
use App\Models\User;
use App\Services\Dashboard\Crud\AttendanceService;
use App\Services\Dashboard\Employee\EmployeeService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Spatie\Permission\Models\Role;
use Carbon\Carbon;
use Carbon\CarbonPeriod;

class EmployeeDashboardController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    protected EmployeeService $employeeService;
    protected AttendanceService $attendanceService;

    public function __construct()
    {
        // Pasamos el modelo que vamos a usar
        $this->employeeService = new EmployeeService();
        $this->attendanceService = new AttendanceService();
    }

    //  getOrdersByTag

    public function index(Store $store)
    {
        //
        try {

            $employees = $store->employees()->with(['user.roles', 'attendances'])->get();

            $employees = $employees->map(function ($employee) {

                $completeAttendances = $this->attendanceService
                    ->completeRangeEmployee($employee, $employee->attendances);

                // Convertimos a colección por si viene como array
                $collection = collect($completeAttendances);

                $employee->attendances = $completeAttendances;

                $employee->total_minutes = $collection->sum('minutes');
                $employee->total_minutes_computed = $collection->sum('minutes_computed');

                return $employee;
            });

            // $employees = FlatUserResource::collection( //FlatUserResource aplana los datos de usuario para evitar usar Employee->user en el front sino todo plano
            //     $store->employees()->get()
            // );

            return responseOk($employees, 'Employees obtenidos correctamente');
        } catch (\Throwable $th) {
            //throw $th;
            Log::info($th);
            return responseError("Ha ocurrido un error interno al obtener los datos de los empleados");
        }
    }

    public function search(Store $store, SearchRequest $request)
    {
        if ($request->hasNoFilters()) {
            return $this->index($store);
        }

        $employees = Employee::with('user')
            ->search($request->search)
            ->betweenDates($request->start_date, $request->end_date) //Sino hay fechas simplemente pasa de largo, no se considera
            ->limit(10)
            ->get();

        return responseOk($employees);
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
                'comission'       => 'nullable|numeric|min:0|max:100',
                'tag_sales'       => 'nullable|string|max:100',
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
                'comission'       => $validated['comission'] ?? null,
                'password'        => bcrypt($validated['document_number']),
            ]);

            // 2. CREAR EMPLEADO ASOCIADO

            $employee = $user->employee()->create(
                [
                    'salary'     => $validated['salary'] ?? null,
                    'date_entry' => now(), // FECHA DE CREACIÓN DEL REGISTRO
                    'tag_sales' => $validated['tag_sales'] ?? null,
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
    public function  show(Store $store, $employed_id)
    {
        try {

            $employee = Employee::with(
                'user.roles',
                'attendances.employee',
                'schedules'
            )
                ->addSelect([
                    'balance' => Payment::selectRaw("
                                COALESCE(SUM(
                                    CASE 
                                        WHEN direction = 'in' THEN amount
                                        WHEN direction = 'out' THEN -amount
                                        ELSE 0
                                    END
                                ),0)
                            ")
                        ->whereColumn('payments.user_id', 'employees.user_id')
                ])
                ->findOrFail($employed_id);

            // --- LIMPIAR ROLES ---
            $rolesOnlyNames = $employee->user->roles->pluck('name');

            unset($employee->user->roles);

            $employee->user->setRelation('roles', $rolesOnlyNames);


            // --- NORMALIZAR ASISTENCIAS ---
            $completeAttendances = $this->attendanceService->completeRangeEmployee($employee, $employee->attendances);

            $employee->setRelation('attendances', collect($completeAttendances)); //Borra la relacion $employee->attendances y la reeemplaza por $completeAttendances

            return responseOk($employee, "Empleado obtenido correctamente");

            // return responseOk($user, "Empleado obtenido correctamente");
        } catch (\Throwable $th) {
            Log::info($th);
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

            $employee = Employee::with('user')->findOrFail($employed_id);

            Log::info($employee);

            //VALIDACIÓN FLEXIBLE (update parcial o completo)
            $validated = $request->validate([
                'name'            => 'required|string|max:255',
                'email'           => ["required", "email", Rule::unique('users', 'email')->ignore($employee->user->id)],
                'phone'           => 'nullable|string|max:20',
                'document_number' => 'nullable|string|max:20',
                'status'          => 'required|in:0,1',
                'tag_sales'       => 'nullable|string|max:100',
                'roles'           => 'required|array|min:1',
                'roles.*'         => 'string|exists:roles,name',
                'comission'       => 'nullable|numeric|min:0|max:100', //employee comission
                'salary'          => 'nullable|numeric|min:0', //employee salary
                'auto_close_end_time' => 'nullable|boolean',
                'work_time_start' => 'nullable|date_format:H:i:s',
                'work_time_end' => 'nullable|date_format:H:i:s',

            ]);

            DB::beginTransaction();

            // ⭐ 1. Actualizar USER (solo campos presentes)

            $employee->update(
                [
                    'comission' => $validated['comission'] ?? null, //actualiza comision del empleado si llega
                    'salary'    => $validated['salary'] ?? null, //actualiza salario del empleado si llega
                    'tag_sales' => $validated['tag_sales'] ?? null, //actualiza tag_sales del empleado si llega
                    'auto_close_end_time' => $validated['auto_close_end_time'],
                    'work_time_start' => $validated['work_time_start'],
                    'work_time_end' => $validated['work_time_end'],
                ]
            );


            $employee->user->update(
                [
                    'name'            => $validated['name'] ?? null,
                    'email'           => $validated['email'] ?? null,
                    'phone'           => $validated['phone'] ?? null,
                    'document_number' => $validated['document_number'] ?? null,
                    'status'          => $validated['status'] ?? null,
                ]
            );


            // ⭐ 3. Actualizar ROLES si llegan
            if ($request->has('roles')) {
                $employee->user->syncRoles($validated['roles']);
            }


            DB::commit();

            return responseOk(
                $employee->load('user.roles'),
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
    public function orders(Store $store, Request $request)
    {

        $tag = $request->input('tag', 'AYLIN');
        $limit = $request->input('limit', 25);
        $cursor = $request->input('cursor', null);

        return $this->employeeService->getOrdersByTag($tag, $limit, $cursor);
    }

    public function ordersSearch(Store $store, Request $request)
    {

        $tag = $request->input('tag_sales', 'AYLIN');
        $start = $request->input('start_date', null);
        $end = $request->input('end_date', null);

        return $this->employeeService->getOrdersByTagBetween($tag, $start, $end);
    }
}
