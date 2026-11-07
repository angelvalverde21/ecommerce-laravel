<?php

namespace App\Http\Controllers\Api\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\Store;
use App\Services\Dashboard\Crud\AttendanceService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AttendanceDashboardController extends Controller
{
    public function index(Store $store, Request $request, AttendanceService $service)
    {
        $validated = $request->validate([
            'employee_id' => ['nullable', 'integer'],
            'start_date' => ['nullable', 'date_format:Y-m-d'],
            'end_date' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:start_date'],
            'search' => ['nullable', 'string', 'max:255'],
        ]);
        $query = $store->attendances()->with('employee.schedules')
            ->where(function ($query) use ($store) {
                $query->whereNull('employee_id')->orWhereHas('employee', fn ($query) => $query->where('store_id', $store->id));
            });
        if (! empty($validated['search'])) {
            $query->whereHas('employee', fn ($query) => $query->search($validated['search']));
        }
        if (! empty($validated['employee_id'])) {
            $query->where('employee_id', $validated['employee_id']);
        }
        if (! empty($validated['start_date'])) {
            $query->whereDate('date', '>=', $validated['start_date']);
        }
        if (! empty($validated['end_date'])) {
            $query->whereDate('date', '<=', $validated['end_date']);
        }
        $page = $query->reorder()->orderByDesc('date')->orderByDesc('id')->paginate(20);

        return response()->json([
            'data' => $page->getCollection()->map(fn ($row) => $row->employee ? $service->describe($row->employee, $row) : [
                ...$row->toArray(),
                'employee_name' => 'Empleado sin asignar',
                'minutes' => null,
                'minutes_computed' => null,
                'salary_neto' => null,
                'salary_extra' => null,
                'incomplete' => true,
            ])->values(),
            'meta' => ['current_page' => $page->currentPage(), 'last_page' => $page->lastPage(), 'total' => $page->total()],
        ]);
    }

    public function upload(Store $store, Request $request, AttendanceService $service)
    {
        return responseOk($service->upload($store, $request), 'Asistencias importadas correctamente.');
    }

    public function store(Store $store, Request $request, AttendanceService $service)
    {
        $validated = $request->validate($this->rules($store));
        $employee = $store->employees()->findOrFail($validated['employee_id']);
        $attendance = $store->attendances()->create($validated);

        return responseOk($service->describe($employee, $attendance), 'Asistencia registrada correctamente.');
    }

    public function show(Store $store, int $attendance_id, AttendanceService $service)
    {
        $attendance = $store->attendances()->with('employee.schedules')->findOrFail($attendance_id);

        return responseOk($attendance->employee
            ? $service->describe($attendance->employee, $attendance)
            : [...$attendance->toArray(), 'employee_name' => 'Empleado sin asignar']);
    }

    public function destroy(Store $store, int $attendance_id, Request $request)
    {
        abort_unless($request->user()->hasAnyRole(['master', 'ceo', 'admin']), 403);
        $store->attendances()->findOrFail($attendance_id)->delete();

        return responseOk([], 'Asistencia eliminada correctamente.');
    }

    public function update(Store $store, int $attendance_id, Request $request, AttendanceService $service)
    {
        $attendance = $store->attendances()->findOrFail($attendance_id);
        $input = $request->all();
        $request->merge([
            'employee_id' => $input['employee_id'] ?? $attendance->employee_id,
            'date' => $input['date'] ?? $attendance->date,
        ]);
        $validated = $request->validate($this->rules($store, $attendance->id));
        $attendance->update($validated);
        $employee = $store->employees()->findOrFail($attendance->employee_id);

        return responseOk($service->describe($employee, $attendance), 'Asistencia actualizada correctamente.');
    }

    private function rules(Store $store, ?int $id = null): array
    {
        foreach (['check_in', 'check_out'] as $field) {
            if (preg_match('/^\d{2}:\d{2}$/', (string) request($field))) {
                request()->merge([$field => request($field).':00']);
            }
        }

        return [
            'employee_id' => ['required', 'integer', Rule::exists('employees', 'id')->where('store_id', $store->id)],
            'date' => ['required', 'date_format:Y-m-d', Rule::unique('attendances')->where('store_id', $store->id)->where('employee_id', request('employee_id'))->ignore($id)],
            'check_in' => ['nullable', 'required_with:check_out', 'date_format:H:i:s'],
            'check_out' => ['nullable', 'date_format:H:i:s', 'after:check_in'],
            'comment' => ['nullable', 'string', 'max:250'],
        ];
    }
}
