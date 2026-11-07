<?php

namespace App\Services\Dashboard\Crud;

use App\Models\Attendance;
use App\Models\Employee;
use App\Models\Store;
use Carbon\CarbonImmutable;
use Carbon\CarbonPeriod;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AttendanceService
{
    public function upload(Store $store, Request $request): array
    {
        $request->validate(['file' => ['required', 'file', 'mimes:csv,txt', 'max:10240']]);
        $handle = fopen($request->file('file')->getRealPath(), 'rb');
        $daily = [];
        $line = 0;
        try {
            while (($record = fgetcsv($handle, 0, ';')) !== false) {
                $line++;
                if ($line <= 2) {
                    continue;
                }
                if (count($record) < 4) {
                    if (! array_filter($record, fn ($value) => trim((string) $value) !== '')) {
                        continue;
                    }
                    throw ValidationException::withMessages(['file' => 'Faltan columnas separadas por punto y coma en la fila '.$line.'.']);
                }
                $code = trim($record[1] ?? '');
                $value = trim($record[3] ?? '');
                if ($code === '' && $value === '') {
                    continue;
                }
                try {
                    $date = CarbonImmutable::createFromFormat('!d/m/Y H:i', $value);
                    if (! $date || $date->format('d/m/Y H:i') !== $value || $code === '') {
                        throw new \RuntimeException;
                    }
                } catch (\Throwable) {
                    throw ValidationException::withMessages(['file' => 'Formato de fecha o código inválido en la fila '.$line.'.']);
                }
                $key = $code.'_'.$date->toDateString();
                $time = $date->format('H:i:s');
                if (! isset($daily[$key])) {
                    $daily[$key] = ['code' => $code, 'date' => $date->toDateString(), 'entry' => $time, 'exit' => $time];
                } else {
                    $daily[$key]['entry'] = min($daily[$key]['entry'], $time);
                    $daily[$key]['exit'] = max($daily[$key]['exit'], $time);
                }
            }
        } finally {
            fclose($handle);
        }
        if (! $daily) {
            throw ValidationException::withMessages(['file' => 'El archivo no contiene marcaciones válidas después de las dos filas de encabezado.']);
        }

        $employees = $store->employees()
            ->when($request->route('employee_id'), fn ($query, $id) => $query->whereKey($id))
            ->get(['id', 'user_id', 'biometric_code']);
        $rows = [];
        foreach ($daily as $day) {
            $matches = $employees->filter(fn ($employee) => (string) $employee->biometric_code === $day['code']
                || (string) $employee->document_number === $day['code']);
            if ($matches->count() !== 1) {
                throw ValidationException::withMessages(['file' => 'Hay códigos sin empleado o con coincidencias ambiguas. Revisa documento y código biométrico antes de importar. No se guardó ninguna marcación.']);
            }
            $rows[] = [
                'store_id' => $store->id,
                'employee_id' => $matches->first()->id,
                'date' => $day['date'],
                'check_in' => $day['entry'],
                'check_out' => $day['entry'] === $day['exit'] ? null : $day['exit'],
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }
        DB::transaction(function () use ($rows) {
            foreach (array_chunk($rows, 200) as $chunk) {
                Attendance::upsert($chunk, ['store_id', 'employee_id', 'date'], ['check_in', 'check_out', 'updated_at']);
            }
        });

        return ['processed_days' => count($rows), 'employees' => count(array_unique(array_column($rows, 'employee_id')))];
    }

    public function completeRangeEmployee(Employee $employee, Collection $attendances, ?string $from = null, ?string $to = null): array
    {
        $employee->loadMissing('schedules');
        $records = $attendances->keyBy(fn ($attendance) => substr((string) $attendance->date, 0, 10));
        if ($records->isEmpty() && (! $from || ! $to)) {
            return [];
        }
        $dates = $records->keys()->sort()->values();
        $start = CarbonImmutable::parse($from ?? $dates->first())->startOfDay();
        $end = CarbonImmutable::parse($to ?? $dates->last())->startOfDay();
        if ($start->greaterThan($end)) {
            return [];
        }
        $rows = [];
        $schedules = $employee->schedules->keyBy('day_of_week');
        foreach (CarbonPeriod::create($start, $end) as $date) {
            $key = $date->format('Y-m-d');
            $schedule = $schedules->get($date->dayOfWeekIso);
            $attendance = $records->get($key);
            if (! $attendance && ! $schedule) {
                continue;
            }
            $rows[] = $this->describe($employee, $attendance, $key);
        }

        return collect($rows)->sortByDesc('date')->values()->all();
    }

    public function describe(Employee $employee, ?Attendance $attendance, ?string $date = null): array
    {
        $employee->loadMissing('schedules');
        $day = $date ?? substr((string) $attendance->date, 0, 10);
        $schedule = $employee->schedules->firstWhere('day_of_week', CarbonImmutable::parse($day)->dayOfWeekIso);
        $start = $schedule?->start_time ?? $employee->work_time_start;
        $end = $schedule?->end_time ?? $employee->work_time_end;
        $type = $attendance ? 'onsite' : ($schedule?->work_type ?? 'onsite');
        $missing = ! $attendance && ! in_array($type, ['home_office', 'rest'], true);
        $in = $attendance?->check_in;
        $out = $attendance?->check_out;
        $minutes = $in && $out ? $this->minutes($in, $out) : 0;
        if (! $attendance && $type === 'home_office' && $start && $end) {
            $minutes = $this->minutes($start, $end);
        }
        $computed = $minutes;
        $allowExtra = $schedule?->allow_extra_hours ?? $employee->allow_extra_hours ?? true;
        if ($attendance && ! $allowExtra && $in && $out && $end) {
            $limit = CarbonImmutable::parse($end)->addMinutes($employee->tolerance_minutes ?? 15)->format('H:i:s');
            $computed = $this->minutes($in, min($out, $limit));
        }
        $late = $in && $start
            ? CarbonImmutable::parse($start)->diffInMinutes(CarbonImmutable::parse($in), false) > ($employee->tolerance_minutes ?? 15)
            : false;
        $break = $type === 'onsite' && $employee->type === 'fulltime' ? min(60, $computed) : 0;
        $salaryDay = $employee->salary !== null ? round((float) $employee->salary / 30, 0) : null;
        $salaryNet = $salaryDay === null ? null : round($salaryDay * max(0, $computed - $break) / 480, 2);
        if (! $attendance && in_array($type, ['home_office', 'rest'], true)) {
            $salaryNet = $salaryDay;
        } elseif ($missing) {
            $salaryNet = $salaryDay === null ? null : -$salaryDay;
        }

        return [
            'id' => $attendance?->id,
            'store_id' => $employee->store_id,
            'employee_id' => $employee->id,
            'employee_name' => $employee->name,
            'employee_work_type' => $employee->type,
            'date' => $day,
            'check_in' => $in,
            'check_out' => $out,
            'comment' => $attendance?->comment,
            'minutes' => $minutes,
            'minutes_computed' => $computed,
            'break_minutes' => $break,
            'paid_minutes' => max(0, $computed - $break),
            'salary_day' => $salaryDay,
            'salary_neto' => $salaryNet,
            'salary_extra' => $salaryDay === null ? null : ($missing ? -$salaryDay : round($salaryNet - $salaryDay, 2)),
            'missing' => $missing,
            'is_late' => $late,
            'work_type' => $type,
            'missing_text' => $attendance ? null : match ($type) {
                'home_office' => 'REMOTO',
                'rest' => 'DESCANZO',
                default => 'FALTO',
            },
            'incomplete' => $attendance && (! $in || ! $out),
        ];
    }

    private function minutes(string $start, string $end): int
    {
        return max(0, (int) CarbonImmutable::parse($start)->diffInMinutes(CarbonImmutable::parse($end), false));
    }
}
