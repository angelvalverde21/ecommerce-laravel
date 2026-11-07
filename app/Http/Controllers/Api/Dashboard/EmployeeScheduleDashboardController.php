<?php

namespace App\Http\Controllers\Api\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\Store;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class EmployeeScheduleDashboardController extends Controller
{
    public function index(Store $store, int $employee_id)
    {
        return responseOk($store->employees()->findOrFail($employee_id)->schedules()->get());
    }

    public function store(Store $store, int $employee_id, Request $request)
    {
        $employee = $store->employees()->findOrFail($employee_id);
        $values = $this->validateSchedule($request, $store, $employee_id);
        $schedule = $employee->schedules()->create([...$values, 'store_id' => $store->id]);

        return responseOk($schedule, 'Horario creado correctamente.');
    }

    public function show(Store $store, int $employee_id, int $schedule_id)
    {
        return responseOk($store->employees()->findOrFail($employee_id)->schedules()->findOrFail($schedule_id));
    }

    public function update(Store $store, int $employee_id, int $schedule_id, Request $request)
    {
        $schedule = $store->employees()->findOrFail($employee_id)->schedules()->findOrFail($schedule_id);
        $schedule->update($this->validateSchedule($request, $store, $employee_id, $schedule_id));

        return responseOk($schedule, 'Horario actualizado correctamente.');
    }

    public function destroy(Store $store, int $employee_id, int $schedule_id)
    {
        $store->employees()->findOrFail($employee_id)->schedules()->findOrFail($schedule_id)->delete();

        return responseOk([], 'Horario eliminado. Las marcaciones de asistencia se conservan.');
    }

    private function validateSchedule(Request $request, Store $store, int $employeeId, ?int $id = null): array
    {
        foreach (['start_time', 'end_time'] as $field) {
            if (preg_match('/^\d{2}:\d{2}$/', (string) $request->input($field))) {
                $request->merge([$field => $request->input($field).':00']);
            }
        }

        return $request->validate([
            'work_type' => ['required', 'in:home_office,onsite,rest'],
            'day_of_week' => ['required', 'integer', 'between:1,7', Rule::unique('employee_schedules')->where('employee_id', $employeeId)->where('store_id', $store->id)->ignore($id)],
            'start_time' => ['nullable', 'required_unless:work_type,rest', 'date_format:H:i:s'],
            'end_time' => ['nullable', 'required_unless:work_type,rest', 'date_format:H:i:s', 'after:start_time'],
            'allow_extra_hours' => ['required', 'boolean'],
            'comments' => ['nullable', 'string', 'max:1000'],
        ]);
    }
}
