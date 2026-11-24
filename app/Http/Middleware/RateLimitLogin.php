<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Symfony\Component\HttpFoundation\Response;

class RateLimitLogin
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->is('login') || $request->is('callback')) {
            $key = 'login:'.$request->ip();
            $maxAttempts = app()->environment('testing') ? 1000 : 20;

            if (RateLimiter::tooManyAttempts($key, $maxAttempts)) {
                abort(429, 'Muitas tentativas de login. Tente novamente em instantes.');
            }

            RateLimiter::hit($key, 60);
        }

        return $next($request);
    }
}
