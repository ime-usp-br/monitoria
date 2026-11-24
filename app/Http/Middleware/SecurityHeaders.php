<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('X-Frame-Options', 'SAMEORIGIN');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->headers->set('Reporting-Endpoints', 'csp-endpoint="/csp-report"');

        $scriptSrc = app()->isProduction()
            ? "'self'"
            : "'self' 'unsafe-inline' 'unsafe-eval' http://localhost:5173 http://127.0.0.1:5173";

        $csp = "default-src 'self'; ".
            "script-src {$scriptSrc}; ".
            "style-src 'self' 'unsafe-inline'; ".
            "img-src 'self' data:; ".
            "font-src 'self'; ".
            "connect-src 'self'; ".
            "frame-ancestors 'self'; ".
            "base-uri 'self'; ".
            "form-action 'self'; ".
            "object-src 'none'; ".
            "report-uri /csp-report; ".
            "report-to csp-endpoint;";

        $response->headers->set(
            app()->isProduction() ? 'Content-Security-Policy' : 'Content-Security-Policy-Report-Only',
            $csp
        );

        if (app()->isProduction() && $request->isSecure()) {
            $response->headers->set(
                'Strict-Transport-Security',
                'max-age=31536000; includeSubDomains'
            );
        }

        return $response;
    }
}
