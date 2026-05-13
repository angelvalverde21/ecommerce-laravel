<?php

namespace App\Http\Controllers\Api\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\Store;
use App\Services\Dashboard\Crud\AttendanceService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class AttendanceDashboardController extends Controller
{
    /**
     * Display a listing of the resource.
     */

    protected AttendanceService $attendanceService;

    public function __construct()
    {
        $this->attendanceService = new AttendanceService();
    }

    public function index(Store $store)
    {
        //
        $attendances = $store->attendances()->with('employee')->get();
        return responseOk($attendances, 'Asistencias obtenidas correctamente');
    }

    /**
     * Show the form for upload a new un lote.
     */

    // Función para guardar un usuario con validación

    public function upload(Store $store, Request $request)
    {

        try {
            
            $this->attendanceService->upload($store, $request);

            return responseOk([], 'Asistencias subidas correctamente');

        } catch (\Throwable $th) {

            Log::info($th);

            return responseError("Ha ocurrido un error al subir el archivo");

        }


    }

    public function getEmployee(string $param): ?int
    {

        switch ($param) {
            case '1':
                //Ayin
                return 3;
                break;

            case '2':
                //Jenifer
                return 2;
                break;

            case '5':
                //Fiorella
                return 6;
                break;

            case '76540879':
                //Marina
                return 4;
                break;

            case '42412498':
                //Angel
                return 1;
                break;

            default:
                return null;
                break;
        }
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(Store $store)
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
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Store $store, $attendance_id, Request $request)
    {
        $attendance = $store->attendances()->findOrFail($attendance_id);
        $attendance->update($request->all());

        return responseOk($attendance, 'Asistencia actualizada correctamente');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Store $store, $attendance_id)
    {
        //
    }
}
