<?php

namespace App\Services\Dashboard\Employee;

use App\Models\Store;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Http\Request;


class EmployeeAttendanceService
{

    public function buildAttendances($employee)
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

        return $data;
    }
}
