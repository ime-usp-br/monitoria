<?php

namespace App\Http\Middleware;

use Illuminate\Http\Middleware\TrustHosts as Middleware;
use Illuminate\Http\Request;

class TrustHosts extends Middleware
{
    public function handle(Request $request, $next)
    {
        if (app()->isProduction()) {
            $host = $request->getHost();
            $allowed = $this->hosts();

            if (! in_array($host, $allowed, true)) {
                abort(400, 'Host header inválido.');
            }
        }

        return parent::handle($request, $next);
    }

    protected function shouldSpecifyTrustedHosts()
    {
        return app()->isProduction() || parent::shouldSpecifyTrustedHosts();
    }

    public function hosts(): array
    {
        $host = parse_url((string) config('app.url'), PHP_URL_HOST);

        $hosts = array_filter([$host]);

        if (! app()->isProduction()) {
            $hosts[] = 'localhost';
            $hosts[] = '127.0.0.1';
        }

        return array_values(array_unique($hosts));
    }
}
