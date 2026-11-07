<?php

namespace App\Http\Controllers\Api\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Models\Purchase;
use App\Models\Store;
use Illuminate\Http\Request;

class EmployeePaymentDashboardController extends Controller
{
    public function index(Store $store, int $employee_id, Request $request)
    {
        $employee = $store->employees()->findOrFail($employee_id);
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:255'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
        ]);
        $payments = $employee->payments()->where('store_id', $store->id)->with(['images', 'gateway']);
        // user_id is the operator who recorded a payment, not its beneficiary.
        // Preserve this separate audit report for old linked accounts.
        $purchases = Payment::where('store_id', $store->id)->where('paymentable_type', Purchase::class)
            ->where('user_id', $employee->user_id ?? -1)->with(['images', 'gateway'])->orderByDesc('date');
        foreach ([$payments, $purchases] as $query) {
            if (! empty($filters['search'])) {
                $query->where('comment', 'like', '%'.$filters['search'].'%');
            }
            if (! empty($filters['start_date'])) {
                $query->whereDate('date', '>=', $filters['start_date']);
            }
            if (! empty($filters['end_date'])) {
                $query->whereDate('date', '<=', $filters['end_date']);
            }
        }

        return responseOk([
            'employee_payments' => $payments->get(),
            'purchase_payments' => $purchases->get(),
        ], 'Movimientos del empleado obtenidos correctamente.');
    }

    public function search(Store $store, int $employee_id, Request $request)
    {
        return $this->index($store, $employee_id, $request);
    }
}
