<?php

namespace App\Services\Dashboard\Employee;

use App\Models\Store;
use App\Services\Dashboard\Crud\AttendanceService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Http\Request;


class EmployeeAttendanceService
{

    protected AttendanceService $attendanceService;

    public function __construct()
    {
        $this->attendanceService = new AttendanceService();
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


        $data = $this->attendanceService->completeRange($employee->attendances);

        return $data;
    }
}
