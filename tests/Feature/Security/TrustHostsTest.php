<?php

namespace Tests\Feature\Security;

use App\Http\Middleware\TrustHosts;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class TrustHostsTest extends TestCase
{
    public function test_host_desconhecido_e_rejeitado_em_producao(): void
    {
        $original = $this->app['env'];
        $this->app['env'] = 'production';

        try {
            $middleware = new TrustHosts($this->app);
            $request = Request::create('http://evil.example/');

            $this->expectException(HttpException::class);
            $middleware->handle($request, fn () => new Response('ok'));
        } finally {
            $this->app['env'] = $original;
        }
    }

    public function test_host_do_app_url_e_aceito_em_producao(): void
    {
        $original = $this->app['env'];
        $this->app['env'] = 'production';

        try {
            $middleware = new TrustHosts($this->app);
            $host = parse_url((string) config('app.url'), PHP_URL_HOST);
            $request = Request::create('http://'.$host.'/');

            $response = $middleware->handle($request, fn () => new Response('ok'));

            $this->assertSame(200, $response->getStatusCode());
        } finally {
            $this->app['env'] = $original;
        }
    }
}
