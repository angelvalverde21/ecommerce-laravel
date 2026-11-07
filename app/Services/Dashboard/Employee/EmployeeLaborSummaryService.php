<?php

namespace App\Services\Dashboard\Employee;

use App\Models\Store;
use App\Services\Dashboard\Crud\AttendanceService;
use Carbon\CarbonImmutable;

class EmployeeLaborSummaryService
{
    public function __construct(private AttendanceService $attendanceService) {}

    public function forEmployee(Store $store, int $employeeId): array
    {
        $today = CarbonImmutable::today(config('app.timezone'));
        $start = $today->startOfMonth()->day($today->day <= 15 ? 1 : 16);
        $ranges = [];
        for ($index = 0; $index < 3; $index++) {
            $end = $start->day === 1 ? $start->day(15) : $start->endOfMonth();
            $ranges[] = ['start' => $start, 'end' => $end];
            $previous = $start->subDay();
            $start = $previous->startOfMonth()->day($previous->day <= 15 ? 1 : 16);
        }
        $employee = $store->employees()->with([
            'schedules',
            'attendances' => fn ($query) => $query->whereBetween('date', [$ranges[2]['start']->toDateString(), $today->toDateString()]),
        ])->findOrFail($employeeId);

        $periods = [];
        foreach ($ranges as $index => $range) {
            $through = $range['end']->min($today);
            $from = $range['start'];
            if ($employee->date_entry) {
                $from = $from->max(CarbonImmutable::parse($employee->date_entry));
            }
            $records = $employee->attendances->filter(fn ($row) => $row->date >= $range['start']->toDateString()
                && $row->date <= $through->toDateString())->values();
            // Existing marks remain visible even if an entry date was entered later.
            if ($records->isNotEmpty()) {
                $from = $from->min(CarbonImmutable::parse($records->min('date')));
            }
            $rows = collect($this->attendanceService->completeRangeEmployee(
                $employee, $records, $from->toDateString(), $through->toDateString()
            ));
            $periods[] = [
                'id' => $range['start']->toDateString(),
                'label' => $range['start']->day === 1 ? 'Primera quincena' : 'Segunda quincena',
                'start_date' => $range['start']->toDateString(),
                'end_date' => $range['end']->toDateString(),
                'computed_through' => $through->toDateString(),
                'is_current' => $index === 0,
                'recorded_days' => $rows->whereNotNull('id')->count(),
                'worked_minutes' => $rows->sum('paid_minutes'),
                'late_days' => $rows->where('is_late', true)->count(),
                'missing_days' => $rows->where('missing', true)->count(),
                'incomplete_days' => $rows->where('incomplete', true)->count(),
                'remote_days' => $rows->where('work_type', 'home_office')->count(),
                'attendances' => $rows->values()->all(),
            ];
        }

        return [
            'employee' => $employee->only(['id', 'name', 'salary', 'type']),
            'as_of' => $today->toDateString(),
            'periods' => $periods,
        ];
    }
}
