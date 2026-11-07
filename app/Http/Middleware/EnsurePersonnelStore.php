<?php

namespace App\Http\Middleware;

use App\Models\Store;
use Closure;
use Illuminate\Http\Request;

class EnsurePersonnelStore
{
    public function handle(Request $request, Closure $next)
    {
        $store = $request->route('store');
        $user = $request->user();
        abort_unless($store instanceof Store && $user, 403);
        abort_unless($user->hasRole('master') || $user->stores()->whereKey($store->id)->exists(), 403);

        return $next($request);
    }
}
