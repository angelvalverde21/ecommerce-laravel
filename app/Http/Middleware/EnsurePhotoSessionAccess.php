<?php
namespace App\Http\Middleware;
use Closure;
use Illuminate\Http\Request;

class EnsurePhotoSessionAccess
{
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user(); $store = $request->route('store');
        abort_unless($user, 403);
        abort_unless($user->hasRole('master') || $user->stores()->whereKey($store->id)->exists(), 403);
        if (!$user->hasAnyRole(['master', 'ceo', 'admin', 'marketing'])) {
            $index = \App\Http\Controllers\Api\Dashboard\PhotoSessionDashboardController::class.'@index';
            abort_unless($request->isMethod('GET') && $request->route()->getActionName() === $index
                && \Illuminate\Support\Facades\DB::table('employees')->where('store_id', $store->id)->where('user_id', $user->id)->where('status', true)->exists(), 403);
        }
        return $next($request);
    }
}
