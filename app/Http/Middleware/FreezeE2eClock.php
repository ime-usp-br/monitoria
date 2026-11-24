<?php

namespace App\Http\Middleware;

use Carbon\Carbon;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class FreezeE2eClock
{
    public function handle(Request $request, Closure $next): Response
    {
        $frozen = env('E2E_FROZEN_NOW');

        if (! app()->environment('testing') || ! $frozen) {
            return $next($request);
        }

        // OAuth usa o relógio real para validar assinatura/timestamp do provedor.
        if ($request->is('login', 'callback', 'logout')) {
            return $next($request);
        }

        Carbon::setTestNow(Carbon::parse($frozen, config('app.timezone')));

        try {
            return $next($request);
        } finally {
            Carbon::setTestNow();
        }
    }
}
