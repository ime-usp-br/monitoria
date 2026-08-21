<?php

namespace Tests\Feature\Scenario;

use App\Models\SchoolTerm;
use Carbon\Carbon;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class SchoolTermScenarioTest extends ScenarioTestCase
{
    public function test_cen_schoolterm_001_criar_periodo_aberto_quando_outro_aberto_existe_bloqueado()
    {
        $this->secretaria();
        $admin = $this->admin();

        $open = $this->createOpenTerm();
        $countBefore = SchoolTerm::count();

        $this->from('/schoolterms')
            ->actingAs($admin)
            ->post('/schoolterms', $this->payload(['year' => 2027, 'period' => '1° Semestre', 'status' => 'Aberto']))
            ->assertRedirect('/schoolterms');

        $this->assertSessionHasWarningContaining('apenas um período letivo');
        $this->assertSame($countBefore, SchoolTerm::count());
        $this->assertDatabaseHas('school_terms', ['id' => $open->id, 'status' => 'Aberto']);
    }

    public function test_cen_schoolterm_002_criar_periodo_fechado_com_outro_aberto_eh_permitido()
    {
        $admin = $this->admin();
        $this->createOpenTerm();

        $this->actingAs($admin)
            ->post('/schoolterms', $this->payload(['year' => 2027, 'period' => '1° Semestre', 'status' => 'Fechado']))
            ->assertRedirect('/schoolterms');

        $this->assertDatabaseHas('school_terms', ['year' => 2027, 'period' => '1° Semestre', 'status' => 'Fechado']);
        $this->assertSame(1, SchoolTerm::where('status', 'Aberto')->count());
    }

    public function test_cen_schoolterm_003_criar_periodo_com_inicio_depois_do_fim_rejeitado()
    {
        $admin = $this->admin();
        $this->createOpenTerm();

        $this->actingAs($admin)
            ->from('/schoolterms')
            ->post('/schoolterms', $this->payload([
                'year' => 2027,
                'start_date_requisitions' => '28/02/2026',
                'end_date_requisitions' => '01/02/2026',
            ]))
            ->assertSessionHasErrors('start_date_requisitions');

        $this->assertDatabaseMissing('school_terms', ['year' => 2027]);
    }

    public function test_cen_schoolterm_004_ano_periodo_duplicado_atualiza_em_vez_de_duplicar()
    {
        $admin = $this->admin();
        $open = $this->createOpenTerm();
        $this->assertSame(1, SchoolTerm::count());

        $this->actingAs($admin)
            ->post('/schoolterms', $this->payload([
                'year' => $open->year,
                'period' => $open->period,
                'status' => 'Fechado',
            ]))
            ->assertRedirect('/schoolterms');

        $this->assertSame(1, SchoolTerm::count());
        $this->assertDatabaseHas('school_terms', ['id' => $open->id, 'status' => 'Fechado', 'year' => $open->year]);
    }

    public function test_cen_schoolterm_005_validacoes_do_formulario()
    {
        $admin = $this->admin();

        $cases = [
            'year_nao_numerico' => ['year' => 'abc'],
            'period_invalido' => ['period' => '3° Semestre'],
            'status_invalido' => ['status' => 'Semi-aberto'],
            'max_enrollments_zero' => ['max_enrollments' => 0],
            'max_enrollments_nao_numerico' => ['max_enrollments' => 'x'],
            'data_formato_invalido' => ['start_date_requisitions' => '2026-02-01'],
        ];

        foreach ($cases as $key => $overrides) {
            $this->actingAs($admin)->post('/schoolterms', $this->payload($overrides))->assertSessionHasErrors();
        }
    }

    public function test_cen_schoolterm_006_edital_pdf_armazenado_sob_pasta_do_periodo()
    {
        $admin = $this->admin();

        $this->actingAs($admin)->post('/schoolterms', $this->payload([
            'year' => 2026,
            'period' => '1° Semestre',
        ]))->assertRedirect('/schoolterms');

        $term = SchoolTerm::where('year', 2026)->where('period', '1° Semestre')->first();
        $this->assertNotNull($term);
        $this->assertStringStartsWith('20261/', $term->public_notice_file_path);
    }

    public function test_cen_schoolterm_007_edital_nao_pdf_rejeitado()
    {
        $admin = $this->admin();

        $this->actingAs($admin)
            ->post('/schoolterms', $this->payload(['public_notice' => UploadedFile::fake()->create('edital.txt', 100, 'text/plain')]))
            ->assertSessionHasErrors('public_notice');
    }

    public function test_cen_schoolterm_008_edital_acima_de_1000kb_rejeitado()
    {
        $admin = $this->admin();

        $this->actingAs($admin)
            ->post('/schoolterms', $this->payload(['public_notice' => UploadedFile::fake()->create('edital.pdf', 1024, 'application/pdf')]))
            ->assertSessionHasErrors('public_notice');
    }

    public function test_cen_schoolterm_009_atualizar_para_aberto_com_outro_aberto_bloqueado()
    {
        $admin = $this->admin();
        $a = $this->createOpenTerm();
        $b = SchoolTerm::create(array_merge($this->openTermAttributes(), [
            'year' => 2027,
            'period' => '1° Semestre',
            'status' => 'Fechado',
        ]));

        $this->from('/schoolterms')
            ->actingAs($admin)
            ->put('/schoolterms/'.$b->id, $this->payload(['year' => 2027, 'status' => 'Aberto']))
            ->assertRedirect('/schoolterms');

        $this->assertSessionHasWarningContaining('apenas um período letivo');
        $this->assertDatabaseHas('school_terms', ['id' => $b->id, 'status' => 'Fechado']);
        $this->assertDatabaseHas('school_terms', ['id' => $a->id, 'status' => 'Aberto']);
    }

    public function test_cen_schoolterm_010_atualizar_proprio_periodo_aberto_manter_aberto_permitido()
    {
        $admin = $this->admin();
        $a = $this->createOpenTerm();

        $this->from('/schoolterms')
            ->actingAs($admin)
            ->put('/schoolterms/'.$a->id, $this->payload(['year' => $a->year, 'period' => $a->period, 'status' => 'Aberto', 'max_enrollments' => 9]))
            ->assertRedirect('/schoolterms');

        $this->assertDatabaseHas('school_terms', ['id' => $a->id, 'status' => 'Aberto', 'max_enrollments' => 9]);
        $this->assertSame(1, SchoolTerm::where('status', 'Aberto')->count());
    }

    public function test_cen_schoolterm_011_atualizar_de_fechado_para_aberto_sem_outro_aberto_permitido()
    {
        $admin = $this->admin();
        $b = SchoolTerm::create(array_merge($this->openTermAttributes(), [
            'year' => 2027,
            'period' => '1° Semestre',
            'status' => 'Fechado',
        ]));

        $this->from('/schoolterms')
            ->actingAs($admin)
            ->put('/schoolterms/'.$b->id, $this->payload(['year' => 2027, 'status' => 'Aberto']))
            ->assertRedirect('/schoolterms');

        $this->assertDatabaseHas('school_terms', ['id' => $b->id, 'status' => 'Aberto']);
        $this->assertSame(1, SchoolTerm::where('status', 'Aberto')->count());
    }

    public function test_cen_schoolterm_012_substituir_edital_na_atualizacao()
    {
        $admin = $this->admin();
        $term = $this->createOpenTerm();
        $oldPath = $term->public_notice_file_path;

        $newFile = UploadedFile::fake()->create('novo-edital.pdf', 100, 'application/pdf');

        $this->from('/schoolterms')
            ->actingAs($admin)
            ->put('/schoolterms/'.$term->id, $this->payload(['public_notice' => $newFile]))
            ->assertRedirect('/schoolterms');

        $term->refresh();
        $this->assertNotSame($oldPath, $term->public_notice_file_path);
        $this->assertStringStartsWith($term->year.$term->period[0].'/', $term->public_notice_file_path);
    }

    public function test_cen_schoolterm_013_atualizacao_sem_edital_mantem_caminho()
    {
        $admin = $this->admin();
        $term = $this->createOpenTerm();
        $oldPath = $term->public_notice_file_path;

        $payload = $this->payload();
        unset($payload['public_notice']);

        $this->from('/schoolterms')
            ->actingAs($admin)
            ->put('/schoolterms/'.$term->id, $payload)
            ->assertRedirect('/schoolterms');

        $term->refresh();
        $this->assertSame($oldPath, $term->public_notice_file_path);
    }

    public function test_cen_schoolterm_014_baixar_edital_com_caminho_valido()
    {
        Storage::put('20261/edital.pdf', 'conteudo do edital');

        $this->post('/schoolterms/download', ['path' => '20261/edital.pdf'])
            ->assertDownload('edital_monitoria.pdf');
    }

    public function test_cen_schoolterm_015_baixar_edital_com_caminho_inexistente_falha()
    {
        $this->from('/schoolterms')
            ->post('/schoolterms/download', ['path' => '20261/nao-existe.pdf'])
            ->assertSessionHasErrors('path');
    }

    public function test_cen_schoolterm_016_deletar_periodo_eh_stub_vazio()
    {
        $admin = $this->admin();
        $term = $this->createOpenTerm();

        $this->actingAs($admin)->delete('/schoolterms/'.$term->id);

        $this->assertDatabaseHas('school_terms', ['id' => $term->id]);
    }

    public function test_cen_schoolterm_017_index_lista_periodos_em_ordem_decrescente()
    {
        $this->createClosedTerm(); // 2025, 2° Semestre
        $this->createOpenTerm();   // 2026, 1° Semestre
        SchoolTerm::create(array_merge($this->openTermAttributes(), ['year' => 2027, 'period' => '2° Semestre', 'status' => 'Fechado']));

        $secretaria = $this->secretaria();
        $response = $this->actingAs($secretaria)->get('/schoolterms');

        $response->assertOk();
        $periodos = $response->viewData('periodos');
        $keys = $periodos->map(fn ($p) => $p->year.'-'.$p->period)->values()->all();

        $expected = $periodos->map(fn ($p) => $p->year.'-'.$p->period)->sortDesc()->values()->all();
        $this->assertSame($expected, $keys);
    }

    public function test_cen_schoolterm_018_acessos_sem_permissao_negados()
    {
        $semPermissao = $this->createUser('Monitor');
        $term = $this->createOpenTerm();

        $this->actingAs($semPermissao)->get('/schoolterms')->assertForbidden();
        $this->actingAs($semPermissao)->get('/schoolterms/create')->assertForbidden();
        $this->actingAs($semPermissao)->post('/schoolterms', $this->payload())->assertForbidden();
        $this->actingAs($semPermissao)->get('/schoolterms/'.$term->id.'/edit')->assertForbidden();
        $this->actingAs($semPermissao)->put('/schoolterms/'.$term->id, $this->payload())->assertForbidden();
        $this->actingAs($semPermissao)->delete('/schoolterms/'.$term->id)->assertForbidden();
    }

    public function test_cen_schoolterm_019_persistencia_de_datas_normalizada_mutators()
    {
        $term = new SchoolTerm(array_merge($this->openTermAttributes(), [
            'year' => 2026,
            'start_date_requisitions' => '01/02/2026',
            'end_date_requisitions' => '28/02/2026',
        ]));
        $term->save();

        $this->assertSame('2026-02-01 00:00:00', DB::table('school_terms')->where('id', $term->id)->value('start_date_requisitions'));
        $this->assertSame('2026-02-28 23:59:59', DB::table('school_terms')->where('id', $term->id)->value('end_date_requisitions'));

        $fresh = SchoolTerm::find($term->id);
        $this->assertSame('01/02/2026', $fresh->start_date_requisitions);
        $this->assertSame('28/02/2026', $fresh->end_date_requisitions);
    }

    public function test_cen_schoolterm_020_auxiliar_de_periodo_de_solicitacao()
    {
        $term = new SchoolTerm(array_merge($this->openTermAttributes(), [
            'start_date_requisitions' => '01/02/2026',
            'end_date_requisitions' => '28/02/2026',
        ]));
        $term->save();

        Carbon::setTestNow('2026-02-15 12:00:00');
        $this->assertSame(1, SchoolTerm::isRequisitionPeriod());

        Carbon::setTestNow('2026-03-01 12:00:00');
        $this->assertSame(0, SchoolTerm::isRequisitionPeriod());

        Carbon::setTestNow();
    }

    public function test_cen_schoolterm_021_auxiliar_de_periodo_de_inscricao()
    {
        $term = new SchoolTerm(array_merge($this->openTermAttributes(), [
            'start_date_enrollments' => '01/03/2026',
            'end_date_enrollments' => '31/03/2026',
        ]));
        $term->save();

        Carbon::setTestNow('2026-03-10 12:00:00');
        $this->assertSame(1, SchoolTerm::isEnrollmentPeriod());

        Carbon::setTestNow('2026-04-01 12:00:00');
        $this->assertSame(0, SchoolTerm::isEnrollmentPeriod());

        Carbon::setTestNow();
    }

    public function test_cen_schoolterm_022_auxiliar_de_periodo_de_avaliacao()
    {
        $term = new SchoolTerm(array_merge($this->openTermAttributes(), [
            'start_date_evaluations' => '01/07/2026',
            'end_date_evaluations' => '31/07/2026',
        ]));
        $term->save();

        Carbon::setTestNow('2026-07-15 12:00:00');
        $this->assertSame(1, $term->isInEvaluationPeriod());
        $this->assertSame(1, $term->fresh()->isInEvaluationPeriod());

        Carbon::setTestNow('2026-08-01 12:00:00');
        $this->assertSame(0, $term->fresh()->isInEvaluationPeriod());

        Carbon::setTestNow();
    }

    // ------------------------------------------------------------------

    protected function payload(array $overrides = []): array
    {
        return array_merge([
            'year' => 2026,
            'period' => '1° Semestre',
            'status' => 'Aberto',
            'max_enrollments' => 5,
            'public_notice' => UploadedFile::fake()->create('edital.pdf', 100, 'application/pdf'),
            'started_at' => '01/01/2026',
            'finished_at' => '31/12/2026',
            'start_date_requisitions' => '01/02/2026',
            'end_date_requisitions' => '28/02/2026',
            'start_date_enrollments' => '01/03/2026',
            'end_date_enrollments' => '31/03/2026',
            'start_date_evaluations' => '01/07/2026',
            'end_date_evaluations' => '31/07/2026',
        ], $overrides);
    }
}