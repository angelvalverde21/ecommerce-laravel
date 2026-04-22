<?php

namespace App\Http\Controllers\Api\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\Store;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Carbon\CarbonPeriod;

class EmployeeAttendanceDashboardController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Store $store, $employee_id)
    {
        $employee = $store->employees()
            ->with('attendances.employee')
            ->findOrFail($employee_id);

        $data = $this->buildAttendances($employee);

        return responseOk(
            $data,
            'Asistencias obtenidas correctamente desde EmployeeAttendanceDashboardController'
        );
    }

    public function search(Store $store, $employee_id, Request $request)
    {
        $start = $request->input('start_date');
        $end   = $request->input('end_date');

        $employee = $store->employees()
            ->with(['attendances' => function ($query) use ($start, $end) {

                if ($start && $end) {
                    $query->whereBetween('date', [$start, $end]);
                }
            }, 'attendances.employee'])
            ->findOrFail($employee_id);

        $data = $this->buildAttendances($employee);

        return responseOk(
            $data,
            'Asistencias filtradas correctamente'
        );
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
    public function store(Request $request)
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

    private function buildAttendances($employee)
    {
        $attendances = $employee->attendances
            ->keyBy(function ($attendance) {
                return Carbon::parse($attendance->date)->format('Y-m-d');
            });

        if ($employee->attendances->isEmpty()) {
            return collect([]);
        }

        $dates = $employee->attendances
            ->pluck('date')
            ->map(fn($d) => Carbon::parse($d));

        $start = $dates->min();
        $end   = $dates->max();

        $period = CarbonPeriod::create($start, $end);

        $completeAttendances = [];

        foreach ($period as $date) {

            if ($date->dayOfWeek == Carbon::SUNDAY) {
                continue;
            }

            $key = $date->format('Y-m-d');

            if ($attendances->has($key)) {

                $attendance = $attendances[$key];
                $attendance->missing = false;

                $completeAttendances[] = $attendance;
            } else {

                $completeAttendances[] = [
                    'id' => null,
                    'employee_id' => $employee->id,
                    'employee' => $employee->load('user'),
                    'check_in' => null,
                    'check_out' => null,
                    'date' => $key,
                    'missing' => true
                ];
            }
        }

        return collect($completeAttendances);
    }
}
