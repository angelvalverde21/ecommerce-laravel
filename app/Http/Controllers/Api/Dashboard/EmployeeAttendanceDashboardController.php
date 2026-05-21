<?php

namespace App\Http\Controllers\Api\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\Store;
use App\Services\Dashboard\Crud\AttendanceService;
use App\Services\Dashboard\Employee\EmployeeAttendanceService;
use Illuminate\Http\Request;


class EmployeeAttendanceDashboardController extends Controller
{

    protected EmployeeAttendanceService $employeeAttendanceService;
    protected AttendanceService $attendanceService;

    public function __construct()
    {
        // Pasamos el modelo que vamos a usar
        $this->employeeAttendanceService = new EmployeeAttendanceService();
        $this->attendanceService = new AttendanceService();
    }

    /**
     * Display a listing of the resource.
     */

    // function countSundays(string $fechaInicio, string $fechaFin): int
    // {
    //     $inicio = Carbon::parse($fechaInicio);
    //     $fin = Carbon::parse($fechaFin);

    //     $domingos = 0;

    //     while ($inicio->lte($fin)) {

    //         if ($inicio->dayOfWeek === Carbon::SUNDAY) {
    //             $domingos++;
    //         }

    //         $inicio->addDay();
    //     }

    //     return $domingos;
    // }

    // function contarDias(string $fechaInicio, string $fechaFin): int
    // {
    //     $inicio = Carbon::parse($fechaInicio);
    //     $fin = Carbon::parse($fechaFin);

    //     // +1 porque queremos incluir ambos extremos
    //     return $inicio->diffInDays($fin) + 1;
    // }


    // function days_works(string $fechaInicio, string $fechaFin): int
    // { 

    //     $days_period = $this->contarDias($fechaInicio, $fechaFin);

    //     $dias_laborables = $days_period - $this->countSundays($fechaInicio, $fechaFin);

    //     return $dias_laborables;

    // }

    public function index(Store $store, int $employee_id)
    {
        $employee = $store->employees()
            ->with('attendances.employee')
            ->findOrFail($employee_id);

        $data = $this->attendanceService->completeRangeEmployee($employee, $employee->attendances);

        return responseOk(
            $data,
            'Asistencias obtenidas correctamente desde EmployeeAttendanceDashboardController'
        );
    }

    // public function hour_works(string $fechaInicio, string $fechaFin): int
    // {

    //     return $this->days_works($fechaInicio, $fechaFin) * 8; // se multiplica por 8 se descuenta una hora por almuerzo

    // }

    public function search(Store $store, $employee_id, Request $request)
    {

        return responseOk($this->employeeAttendanceService->search($store, $employee_id, $request), "Registros de asistencia obtenidos correctamente");
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

    }

    /**
     * Display the specified resource.
     */
    public function show(Store $store)
    {
        //
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
    public function update(Request $request, Store $store)
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
