<?php

namespace App\Http\Controllers\Api\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\Store;
use App\Services\Dashboard\AreaDashboardService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AreaDashboardController extends Controller
{
    public function show(Store $store, Request $request, string $area, AreaDashboardService $service)
    {
        abort_unless(in_array($area, ['sales', 'packing', 'marketing', 'ceo'], true), 404);
        $user = $request->user();
        $roles = ['master', 'ceo', 'admin'];
        if ($area !== 'ceo') {
            $roles[] = $area;
        }
        abort_unless($user && $user->hasAnyRole($roles), 403, 'No tienes acceso a este dashboard.');
        abort_unless(
            $user->hasRole('master') || $user->stores()->where('stores.id', $store->id)->exists(),
            403,
            'No tienes acceso a esta tienda.'
        );
        $validated = $request->validate(['days' => ['sometimes', 'integer', Rule::in([7, 30, 90])]]);

        return responseOk($service->report($store, $area, (int) ($validated['days'] ?? 30)));
    }
}
