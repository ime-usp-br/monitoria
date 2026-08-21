<?php

namespace Tests\Feature\Scenario;

use App\Models\SchoolTerm;

class ReportScenarioTest extends ScenarioTestCase
{
    public function test_cen_report_001_index_exige_permissao_gerar_relatorio()
    {
        $semPermissao = $this->createUser('Monitor');

        $this->actingAs($semPermissao)->get('/reports')->assertForbidden();
    }

    public function test_cen_report_002_index_lista_periodos()
    {
        $secretaria = $this->secretaria();
        $term = $this->createOpenTerm();

        $resp = $this->actingAs($secretaria)->get('/reports');
        $resp->assertOk();

        $this->assertTrue($resp->viewData('schoolterms')->contains('id', $term->id));
    }

    public function test_cen_report_004_make_sem_permissao_e_negado()
    {
        $semPermissao = $this->createUser('Monitor');
        $term = $this->createOpenTerm();

        $this->actingAs($semPermissao)
            ->post('/reports/make', ['periodoId' => $term->id])
            ->assertForbidden();
    }

    public function test_cen_report_005_external_exige_token_ano_e_periodo_corretos()
    {
        $this->setEnv('EXTERNAL_REPORT_TOKEN', 'segredo-token');
        $term = $this->createOpenTerm();

        // sem token
        $this->get('/reports/external')->assertJson(['status' => false]);

        // token incorreto
        $this->get('/reports/external?token=errado&ano='.$term->year.'&periodo=1')->assertJson(['status' => false]);

        // sem ano
        $this->get('/reports/external?token=segredo-token')->assertJson(['status' => false]);

        // sem periodo
        $this->get('/reports/external?token=segredo-token&ano='.$term->year)->assertJson(['status' => false]);
    }

    public function test_cen_report_007_external_com_periodo_inexistente_retorna_erro()
    {
        $this->setEnv('EXTERNAL_REPORT_TOKEN', 'segredo-token');
        $this->createOpenTerm();

        $resp = $this->get('/reports/external?token=segredo-token&ano=1999&periodo=1');
        $resp->assertJson(['status' => false, 'message' => 'Não foi encontrado um semestre que atenda sua busca.']);
    }

    public function test_cen_report_006_external_nao_usa_autenticacao_de_sessao()
    {
        $this->setEnv('EXTERNAL_REPORT_TOKEN', 'segredo-token');
        $term = $this->createOpenTerm();

        // sem usuário autenticado, apenas com token válido
        $resp = $this->get('/reports/external?token=segredo-token&ano='.$term->year.'&periodo=1');
        // retorna JSON (não redireciona para login)
        $this->assertFalse($resp->isRedirect());
    }
}
