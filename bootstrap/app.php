<?php

// use App\Http\Middleware\ForceJsonUnauthenticated;

use App\Exceptions\CustomException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Middleware\HandleCors;
use Illuminate\Support\Facades\Request;

// use App\Http\Middleware\VerifyStore;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        api: __DIR__ . '/../routes/api.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {

        $middleware->web(append: [
            \Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful::class,
        ]);
    })

    ->withExceptions(function (Exceptions $exceptions): void {

        //Recibe todos los errores de tipo CustomException y los convierte en respuestas json
        $exceptions->render(function (CustomException $e, Request $request) {
            return response()->json([
                'message' => $e->getMessage(),
            ], $e->status);
        });
    })->create();
