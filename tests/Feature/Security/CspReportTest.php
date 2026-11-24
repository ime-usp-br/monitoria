<?php

namespace Tests\Feature\Security;

use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class CspReportTest extends TestCase
{
    public function test_endpoint_de_relatorio_csp_aceita_payload_e_registra(): void
    {
        Log::spy();

        $this->postJson('/csp-report', [
            'csp-report' => [
                'document-uri' => 'https://example.test/',
                'violated-directive' => "script-src 'self'",
            ],
        ])->assertNoContent();

        Log::shouldHaveReceived('warning')
            ->once()
            ->withArgs(fn ($message, $context) => $message === 'csp_report'
                && ($context['report']['violated-directive'] ?? null) === "script-src 'self'");
    }

    public function test_endpoint_de_relatorio_csp_remove_query_string_e_ignora_payload_grande(): void
    {
        Log::spy();

        $this->postJson('/csp-report', [
            'csp-report' => [
                'document-uri' => 'https://example.test/page?token=secret',
                'blocked-uri' => 'https://example.test/app.js?x=1',
                'violated-directive' => "script-src 'self'",
            ],
        ])->assertNoContent();

        Log::shouldHaveReceived('warning')
            ->once()
            ->withArgs(function ($message, $context) {
                return $message === 'csp_report'
                    && ($context['report']['document-uri'] ?? null) === 'https://example.test/page'
                    && ($context['report']['blocked-uri'] ?? null) === 'https://example.test/app.js';
            });
    }

    public function test_endpoint_de_relatorio_csp_ignora_payload_grande(): void
    {
        Log::spy();

        $this->call(
            'POST',
            '/csp-report',
            [],
            [],
            [],
            ['CONTENT_TYPE' => 'application/csp-report'],
            str_repeat('x', 16385)
        )->assertNoContent();

        Log::shouldNotHaveReceived('warning');
    }

    public function test_cors_usa_somente_app_url(): void
    {
        $origins = config('cors.allowed_origins');

        $this->assertNotContains('*', $origins);
        $this->assertContains(rtrim((string) config('app.url'), '/'), $origins);
    }
}
