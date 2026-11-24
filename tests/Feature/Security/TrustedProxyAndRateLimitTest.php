<?php

namespace Tests\Feature\Security;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class TrustedProxyAndRateLimitTest extends TestCase
{
    public function test_trusted_proxy_uses_forwarded_client_ip(): void
    {
        Route::get('/__security/ip', fn (Request $request) => $request->ip());

        $this->withServerVariables([
            'REMOTE_ADDR' => '10.1.2.3',
            'HTTP_X_FORWARDED_FOR' => '203.0.113.7',
        ])->get('/__security/ip')->assertSee('203.0.113.7');
    }

    public function test_untrusted_proxy_header_is_ignored(): void
    {
        Route::get('/__security/ip', fn (Request $request) => $request->ip());

        $this->withServerVariables([
            'REMOTE_ADDR' => '203.0.113.5',
            'HTTP_X_FORWARDED_FOR' => '198.51.100.9',
        ])->get('/__security/ip')->assertSee('203.0.113.5');
    }

    public function test_login_rate_limit_is_per_client_ip(): void
    {
        $original = $this->app['env'];
        $this->app['env'] = 'production';

        try {
            for ($i = 0; $i < 20; $i++) {
                $this->withServerVariables([
                    'REMOTE_ADDR' => '10.1.2.3',
                    'HTTP_X_FORWARDED_FOR' => '203.0.113.20',
                ])->get('/login');
            }

            $this->withServerVariables([
                'REMOTE_ADDR' => '10.1.2.3',
                'HTTP_X_FORWARDED_FOR' => '203.0.113.20',
            ])->get('/login')->assertStatus(429);

            $this->withServerVariables([
                'REMOTE_ADDR' => '10.1.2.3',
                'HTTP_X_FORWARDED_FOR' => '203.0.113.21',
            ])->get('/login')->assertRedirect();
        } finally {
            $this->app['env'] = $original;
        }
    }
}
