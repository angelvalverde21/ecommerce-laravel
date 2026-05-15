<?php

namespace App\Http\Controllers\Api\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\Store;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class EmployeeScheduleDashboardController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Store $store, $employee_id)
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(Store $store, $employee_id)
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */

    //  user_id Índice	bigint(20)		UNSIGNED	No	Ninguna			Cambiar Cambiar	Eliminar Eliminar	
    // 3	salary	decimal(10,2)			Sí	NULL			Cambiar Cambiar	Eliminar Eliminar	
    // 4	type	enum('fulltime', 'partime')	utf8mb4_unicode_ci		Sí	NULL			Cambiar Cambiar	Eliminar Eliminar	
    // 5	comission	tinyint(4)			Sí	NULL			Cambiar Cambiar	Eliminar Eliminar	
    // 6	work_time_start	time			Sí	09:00:00			Cambiar Cambiar	Eliminar Eliminar	
    // 7	work_time_end	time			Sí	19:00:00			Cambiar Cambiar	Eliminar Eliminar	
    // 8	tolerance_minutes	int(11)			No	10			Cambiar Cambiar	Eliminar Eliminar	
    // 9	tag_sales	varchar(255)	utf8mb4_unicode_ci		Sí	NULL			Cambiar Cambiar	Eliminar Eliminar	
    // 10	phone


    public function store(Store $store, int $employee_id, Request $request)
    {

        try {
            Log::info($request);
            //
            $validated = $request->validate([
                'work_type'         => ['required', 'in:home_office,onsite'],
                'day_of_week'       => ['nullable', 'integer', 'between:1,7'],
                'start_time' => ['nullable', 'date_format:H:i'],
                'end_time'   => ['required', 'date_format:H:i', 'after:start_time'],
                'allow_extra_hours' => ['required', 'boolean'],
                'comments' => ['nullable', 'string'],
            ]);

            $employee = $store->employees()->findOrFail($employee_id);

            $schedule = $employee->schedules()->create([
                ...$validated,
                'store_id' => $store->id,
            ]);

            return responseOk($schedule, "se ha creado correctamente el registro de horario");
        } catch (\Throwable $th) {
            Log::info($th);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Store $store, $employee_id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Store $store, $employee_id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Store $store, int $employee_id, int $schedule_id)
    {
        try {

            Log::info($request->all());

            $validated = $request->validate([
                'work_type'         => ['required', 'in:home_office,onsite'],
                'day_of_week'       => ['nullable', 'integer', 'between:1,7'],
                'start_time'        => ['nullable', 'date_format:H:i:s'],
                'end_time'          => ['required', 'date_format:H:i:s', 'after:start_time'],
                'allow_extra_hours' => ['required', 'boolean'],
                'comments'          => ['nullable', 'string'],
            ]);

            // 🔹 Verificamos que el employee pertenezca al store
            $employee = $store->employees()->findOrFail($employee_id);

            // 🔹 Verificamos que el schedule pertenezca al employee
            $schedule = $employee->schedules()->findOrFail($schedule_id);

            // 🔹 Actualizamos
            $schedule->update($validated);

            return responseOk($schedule, "Se ha actualizado correctamente el registro de horario");
        } catch (\Throwable $th) {
            Log::error($th);
            return response()->json(['error' => 'Ocurrió un error al actualizar'], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Store $store, $employee_id)
    {
        //
    }
}
