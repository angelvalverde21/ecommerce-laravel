<?php

namespace App\Services\Dashboard\Employee;

use App\Models\Store;
use App\Services\Dashboard\Crud\AttendanceService;
use Illuminate\Http\Request;

class EmployeeAttendanceService
{
    public function __construct(private AttendanceService $attendanceService) {}

    public function index(Store $store, int $employee_id): array
    {
        $employee = $store->employees()->with(['attendances', 'schedules'])->findOrFail($employee_id);

        return $this->attendanceService->completeRangeEmployee($employee, $employee->attendances);
    }

    public function search(Store $store, int $employee_id, Request $request): array
    {
        $dates = $request->validate([
            'start_date' => ['required', 'date_format:Y-m-d'],
            'end_date' => ['required', 'date_format:Y-m-d', 'after_or_equal:start_date', 'before_or_equal:'.now()->addYear()->toDateString()],
        ]);
        abort_if(\Carbon\CarbonImmutable::parse($dates['start_date'])->diffInDays($dates['end_date']) > 366, 422, 'Selecciona un rango de hasta un año.');
        $employee = $store->employees()->with([
            'schedules',
            'attendances' => fn ($query) => $query->whereBetween('date', [$dates['start_date'], $dates['end_date']]),
        ])->findOrFail($employee_id);

        return $this->attendanceService->completeRangeEmployee($employee, $employee->attendances, $dates['start_date'], $dates['end_date']);
    }
}
