<?php

namespace App\Http\Controllers\Api\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\Store;
use App\Services\Dashboard\Crud\AttendanceService;
use App\Services\Dashboard\Employee\EmployeeAttendanceService;
use Illuminate\Http\Request;

class EmployeeAttendanceDashboardController extends Controller
{
    public function __construct(private EmployeeAttendanceService $service) {}

    public function index(Store $store, int $employee_id)
    {
        return responseOk($this->service->index($store, $employee_id));
    }

    public function search(Store $store, int $employee_id, Request $request)
    {
        return responseOk($this->service->search($store, $employee_id, $request));
    }

    public function store(Store $store, int $employee_id, Request $request, AttendanceService $service)
    {
        $store->employees()->findOrFail($employee_id);
        $request->merge(['employee_id' => $employee_id]);

        return app(AttendanceDashboardController::class)->store($store, $request, $service);
    }

    public function upload(Store $store, int $employee_id, Request $request, AttendanceService $service)
    {
        $store->employees()->findOrFail($employee_id);

        return responseOk($service->upload($store, $request));
    }

    public function show(Store $store, int $employee_id, int $attendance_id, AttendanceService $service)
    {
        $store->employees()->findOrFail($employee_id)->attendances()->findOrFail($attendance_id);

        return app(AttendanceDashboardController::class)->show($store, $attendance_id, $service);
    }

    public function update(Store $store, int $employee_id, int $attendance_id, Request $request, AttendanceService $service)
    {
        $store->employees()->findOrFail($employee_id)->attendances()->findOrFail($attendance_id);
        $request->merge(['employee_id' => $employee_id]);

        return app(AttendanceDashboardController::class)->update($store, $attendance_id, $request, $service);
    }

    public function destroy(Store $store, int $employee_id, int $attendance_id, Request $request)
    {
        $store->employees()->findOrFail($employee_id)->attendances()->findOrFail($attendance_id);

        return app(AttendanceDashboardController::class)->destroy($store, $attendance_id, $request);
    }
}
