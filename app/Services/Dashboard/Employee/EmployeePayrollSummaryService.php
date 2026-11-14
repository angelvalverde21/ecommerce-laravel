<?php

namespace App\Services\Dashboard\Employee;

use App\Models\Store;
use App\Services\Dashboard\Crud\AttendanceService;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Schema;

class EmployeePayrollSummaryService
{
    public function __construct(private AttendanceService $attendanceService) {}

    public function forStore(Store $store): array
    {
        if (!Schema::hasTable('attendances') || !Schema::hasTable('employee_schedules')
            || !Schema::hasColumns('employees', ['user_id', 'store_id', 'status', 'payroll_uses_attendance'])) {
            return ['available' => false, 'items' => []];
        }

        $today = CarbonImmutable::today('America/Lima');
        $start = $today->startOfMonth()->day($today->day <= 15 ? 1 : 16);
        $end = $start->day === 1 ? $start->day(15) : $start->endOfMonth();
        $employees = $store->employees()->where('status', true)->with([
            'schedules',
            'attendances' => fn ($query) => $query->whereBetween('date', [$start->toDateString(), $today->toDateString()]),
        ])->get()->sortBy(fn ($employee) => mb_strtolower($employee->name ?? ''))->values();

        $items = [];
        $totals = ['base' => 0, 'discounts' => 0, 'extras' => 0, 'net' => 0];
        $pending = 0;
        foreach ($employees as $employee) {
            $usesAttendance = $employee->payroll_uses_attendance;
            $from = $employee->date_entry ? $start->max(CarbonImmutable::parse($employee->date_entry)) : $start;
            if ($employee->attendances->isNotEmpty()) {
                $from = $from->min(CarbonImmutable::parse($employee->attendances->min('date')));
            }
            $rows = collect($this->attendanceService->completeRangeEmployee($employee, $employee->attendances,
                $from->toDateString(), $today->toDateString()));
            $reason = $employee->salary === null ? 'Sin sueldo configurado'
                : ($usesAttendance && $rows->isEmpty() ? 'Sin asistencias ni horario para calcular' : null);
            $amounts = null;
            if ($reason === null) {
                $base = (int) round(round((float) $employee->salary * 100) / 2);
                $discounts = 0;
                $extras = 0;
                if ($usesAttendance) {
                    foreach ($rows as $row) {
                        $adjustment = (int) round($row['salary_extra'] * 100);
                        $discounts += max(0, -$adjustment);
                        $extras += max(0, $adjustment);
                    }
                }
                $cents = ['base' => $base, 'discounts' => $discounts, 'extras' => $extras, 'net' => $base - $discounts + $extras];
                foreach ($totals as $key => $value) {
                    $totals[$key] += $cents[$key];
                }
                $amounts = array_map(fn ($value) => $value / 100, $cents);
            } else {
                $pending++;
            }
            $items[] = [
                'id' => $employee->id, 'name' => $employee->name,
                'payroll_uses_attendance' => $usesAttendance,
                'amounts' => $amounts, 'pending_reason' => $reason,
            ];
        }

        return [
            'available' => true,
            'start_date' => $start->toDateString(), 'end_date' => $end->toDateString(),
            'computed_through' => $today->toDateString(), 'is_provisional' => $today->lessThan($end),
            'employee_count' => $employees->count(), 'pending_count' => $pending,
            'calculated_count' => $employees->count() - $pending,
            'totals' => array_map(fn ($value) => $value / 100, $totals),
            'total_net' => $pending ? null : $totals['net'] / 100,
            'items' => $items,
        ];
    }
}
