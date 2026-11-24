<?php

namespace Tests\Feature\Security;

use Tests\Feature\Scenario\ScenarioTestCase;

class ReportGenerationTest extends ScenarioTestCase
{
    public function test_falha_do_python_interrompe_a_geracao_do_relatorio(): void
    {
        $env = $this->seedEnvironment();

        config(['services.report.python_cmd' => '/bin/false']);

        $this->actingAs($env['secretaria'])
            ->post('/reports/make', ['periodoId' => $env['term']->id])
            ->assertStatus(500);
    }
}
