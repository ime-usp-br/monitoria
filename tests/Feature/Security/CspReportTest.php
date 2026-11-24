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

    public function test_cors_usa_somente_app_url(): void
    {
        $origins = config('cors.allowed_origins');

        $this->assertNotContains('*', $origins);
        $this->assertContains(rtrim((string) config('app.url'), '/'), $origins);
    }
}
