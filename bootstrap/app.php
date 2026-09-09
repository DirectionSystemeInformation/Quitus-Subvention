<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'role' => \App\Http\Middleware\EnsureUserRole::class,
        ]);

        // The framework default sends an already-authenticated user to the
        // "dashboard" route when they hit a guest-only page (e.g. /login) —
        // but that route is federation-only here, so anyone else (DSHN,
        // admin, DG...) would bounce straight into a 403. Send each role to
        // its own dashboard instead.
        $middleware->redirectUsersTo(fn () => route(home_route_name(auth()->user())));
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->respond(function (\Symfony\Component\HttpFoundation\Response $response, \Throwable $e, $request) {
            if ($response->getStatusCode() === 419 && ! $request->expectsJson()) {
                return redirect()->route('login')->with(
                    'status',
                    'Votre session a expiré. Veuillez réessayer.'
                );
            }

            return $response;
        });
    })->create();
