<?php

namespace App\Http\Controllers\Api\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\Store;

class EmployeeExpenseDashboardController extends Controller
{
    public function index(Store $store, int $employee_id)
    {
        return $store->employees()->findOrFail($employee_id)->payments()
            ->where('store_id', $store->id)->with(['images', 'gateway'])->paginate(15);
    }
}
