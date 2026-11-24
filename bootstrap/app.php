<?php

use App\Http\Middleware\FreezeE2eClock;
use App\Http\Middleware\RateLimitLogin;
use App\Http\Middleware\RedirectIfAuthenticated;
use App\Http\Middleware\SecurityHeaders;
use App\Http\Middleware\TrustHosts;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->prepend(TrustHosts::class);

        if (env('E2E_FROZEN_NOW')) {
            $middleware->web(append: [FreezeE2eClock::class]);
        }

        $middleware->append(RateLimitLogin::class);
        $middleware->append(SecurityHeaders::class);

        $middleware->validateCsrfTokens(except: ['csp-report']);

        $middleware->alias([
            'guest' => RedirectIfAuthenticated::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
