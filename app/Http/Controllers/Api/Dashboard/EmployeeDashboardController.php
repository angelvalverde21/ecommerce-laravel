<?php

namespace App\Http\Controllers\Api\Dashboard;

use App\Http\Controllers\Controller;
use App\Http\Requests\EmployeeRequest;
use App\Models\Store;
use App\Models\User;
use App\Services\Dashboard\Crud\AttendanceService;
use App\Services\Dashboard\Employee\EmployeeAccountService;
use App\Services\Dashboard\Employee\EmployeeLaborSummaryService;
use App\Services\Dashboard\Employee\EmployeeService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class EmployeeDashboardController extends Controller
{
    public function laborSummary(Store $store, int $employee_id, EmployeeLaborSummaryService $service)
    {
        return responseOk($service->forEmployee($store, $employee_id));
    }

    public function index(Store $store, Request $request)
    {
        $validated = $request->validate([
            'search' => ['nullable', 'string', 'max:255'],
            'status' => ['nullable', 'in:0,1'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
        ]);
        $query = $store->employees()->search($validated['search'] ?? null)
            ->betweenDates($validated['start_date'] ?? null, $validated['end_date'] ?? null)
            ->withCount(['attendances', 'schedules'])->with('user.roles');
        if (isset($validated['status'])) {
            $query->where('employees.status', $validated['status']);
        }

        $query->orderBy(User::select('name')->whereColumn('users.id', 'employees.user_id'))->orderBy('employees.id');

        return responseOk($query->get()->map(fn ($employee) => app(EmployeeAccountService::class)->present($employee)), 'Empleados obtenidos correctamente.');
    }

    public function search(Store $store, Request $request)
    {
        return $this->index($store, $request);
    }

    public function blocked(Store $store, Request $request)
    {
        $request->merge(['status' => 0]);

        return $this->index($store, $request);
    }

    public function destroy(Store $store, int $employee_id, Request $request)
    {
        abort_unless($request->user()->hasAnyRole(['master', 'ceo', 'admin']), 403);
        $employee = $store->employees()->findOrFail($employee_id);
        DB::transaction(function () use ($employee) {
            $employee->update(['status' => false]);
            $employee->user()->update(['status' => false]);
        });

        return responseOk($employee, 'Empleado desactivado. Se conservan sus asistencias, horarios y movimientos.');
    }

    public function store(Store $store, EmployeeRequest $request)
    {
        $service = app(EmployeeAccountService::class);
        $employee = $service->save($store, $request->validated());

        return response()->json(['data' => $service->present($employee), 'message' => 'Empleado y cuenta de acceso creados correctamente.'], 201);
    }

    public function show(Store $store, int $employee_id, AttendanceService $attendanceService)
    {
        $employee = $store->employees()->with(['schedules', 'attendances'])->findOrFail($employee_id);
        $employee->setRelation('attendances', collect($attendanceService->completeRangeEmployee($employee, $employee->attendances)));
        $employee->balance = $employee->payments()->where('store_id', $store->id)
            ->reorder()
            ->selectRaw("COALESCE(SUM(CASE WHEN direction = 'in' THEN amount WHEN direction = 'out' THEN -amount ELSE 0 END), 0) AS total")
            ->value('total');

        return responseOk(app(EmployeeAccountService::class)->present($employee), 'Empleado obtenido correctamente.');
    }

    public function update(Store $store, EmployeeRequest $request, int $employee_id)
    {
        $employee = $store->employees()->findOrFail($employee_id);
        app(EmployeeAccountService::class)->save($store, $request->validated(), $employee);

        return $this->show($store, $employee_id, app(AttendanceService::class));
    }

    public function orders(Store $store, Request $request, int $employee_id, EmployeeService $service)
    {
        $employee = $store->employees()->findOrFail($employee_id);
        abort_unless(filled($employee->tag_sales), 422, 'Configura la etiqueta de ventas del empleado.');

        return $service->getOrdersByTag($employee->tag_sales, min(100, max(1, (int) $request->input('limit', 25))), $request->input('cursor'));
    }

    public function ordersSearch(Store $store, Request $request, int $employee_id, EmployeeService $service)
    {
        $employee = $store->employees()->findOrFail($employee_id);
        $dates = $request->validate([
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
        ]);
        abort_unless(filled($employee->tag_sales), 422, 'Configura la etiqueta de ventas del empleado.');

        return $service->getOrdersByTagBetween($employee->tag_sales, $dates['start_date'], $dates['end_date']);
    }
}
