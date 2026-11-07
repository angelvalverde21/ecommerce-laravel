<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class EnsureActiveErpUser
{
    public function handle(Request $request, Closure $next)
    {
        abort_unless($request->user() && (int) $request->user()->status === 1, 403, 'La cuenta de acceso está desactivada.');

        return $next($request);
    }
}
