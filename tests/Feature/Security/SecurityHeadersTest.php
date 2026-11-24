<?php

namespace Tests\Feature\Security;

use App\Http\Middleware\SecurityHeaders;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Tests\TestCase;

class SecurityHeadersTest extends TestCase
{
    public function test_adiciona_headers_de_seguranca_basicos(): void
    {
        $response = (new SecurityHeaders)->handle(
            Request::create('/'),
            fn () => new Response('ok')
        );

        $this->assertSame('nosniff', $response->headers->get('X-Content-Type-Options'));
        $this->assertSame('SAMEORIGIN', $response->headers->get('X-Frame-Options'));
        $this->assertSame('strict-origin-when-cross-origin', $response->headers->get('Referrer-Policy'));
        $this->assertStringContainsString(
            "default-src 'self'",
            (string) $response->headers->get('Content-Security-Policy-Report-Only')
        );
    }

    public function test_hsts_somente_em_producao_e_https(): void
    {
        $original = $this->app['env'];
        $this->app['env'] = 'production';

        try {
            $request = Request::create('https://example.com/', 'GET', [], [], [], ['HTTPS' => 'on']);
            $response = (new SecurityHeaders)->handle($request, fn () => new Response('ok'));

            $this->assertSame(
                'max-age=31536000; includeSubDomains',
                $response->headers->get('Strict-Transport-Security')
            );
        } finally {
            $this->app['env'] = $original;
        }
    }

    public function test_hsts_ausente_fora_de_producao_ou_http(): void
    {
        $response = (new SecurityHeaders)->handle(
            Request::create('/'),
            fn () => new Response('ok')
        );

        $this->assertNull($response->headers->get('Strict-Transport-Security'));
    }
}
