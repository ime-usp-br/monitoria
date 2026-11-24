<?php

namespace Tests\Feature\Security;

use Tests\Feature\Scenario\ScenarioTestCase;

class NoCdnAssetsTest extends ScenarioTestCase
{
    public function test_pagina_nao_carrega_assets_de_cdn(): void
    {
        $env = $this->seedEnvironment();

        $response = $this->actingAs($env['aluno'])->get('/');

        $response->assertOk();
        $response->assertDontSee('cdnjs.cloudflare.com', false);
        $response->assertDontSee('cdn.datatables.net', false);
        $response->assertDontSee('cdn.tiny.cloud', false);
    }
}
