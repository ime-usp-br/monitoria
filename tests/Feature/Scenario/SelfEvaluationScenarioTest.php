<?php

namespace Tests\Feature\Scenario;

use App\Models\SchoolTerm;
use App\Models\Selection;
use App\Models\SelfEvaluation;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\URL;

class SelfEvaluationScenarioTest extends ScenarioTestCase
{
    protected function envWithSelection(bool $evaluationWindowActive = true): array
    {
        $env = $this->seedEnvironment();

        if (! $evaluationWindowActive) {
            $env['term']->update([
                'start_date_evaluations' => now()->subDays(30)->format('d/m/Y'),
                'end_date_evaluations' => now()->subDays(20)->format('d/m/Y'),
            ]);
        }

        $selection = $this->createSelection(['enrollment_id' => $env['enrollment']->id]);

        return ['env' => $env, 'selection' => $selection];
    }

    private function signedCreateUrl(Selection $selection): string
    {
        return URL::signedRoute('selfevaluations.create', ['selectionID' => $selection->id]);
    }

    public function test_cen_selfeval_001_index_exige_permissao_visualizar_auto_avaliacoes()
    {
        $this->createOpenTerm();
        $docente = $this->docente();

        $this->actingAs($docente)->get('/selfevaluations')->assertForbidden();
    }

    public function test_cen_selfeval_002_index_lista_autoavaliacoes_do_periodo()
    {
        $fixture = $this->envWithSelection();
        $selection = $fixture['selection'];
        $se = SelfEvaluation::create([
            'selection_id' => $selection->id,
            'student_amount' => 1,
            'homework_amount' => 2,
            'workload' => 1,
            'comments' => 'ok',
        ]);

        $resp = $this->actingAs($fixture['env']['secretaria'])->get('/selfevaluations');

        $resp->assertOk();
        $this->assertTrue($resp->viewData('ses')->contains('id', $se->id));
    }

    public function test_cen_selfeval_003_student_index_lista_selecoes_do_proprio_aluno()
    {
        $env = $this->seedEnvironment();
        $mySelection = $this->createSelection(['enrollment_id' => $env['enrollment']->id]);

        $otherStudent = $this->createStudent(['nompes' => 'Outro Aluno']);
        $otherClass = $this->createSchoolClass(['school_term_id' => $env['term']->id, 'coddis' => 'MAC0202']);
        $this->createRequisition(['instructor_id' => $env['instructor']->id, 'school_class_id' => $otherClass->id]);
        $otherEnrollment = $this->createEnrollment(['student_id' => $otherStudent->id, 'school_class_id' => $otherClass->id]);
        $otherSelection = $this->createSelection(['enrollment_id' => $otherEnrollment->id]);

        $resp = $this->actingAs($env['aluno'])->get('/students/selfevaluations');
        $resp->assertOk();

        $ids = $resp->viewData('selections')->pluck('id')->all();
        $this->assertContains($mySelection->id, $ids);
        $this->assertNotContains($otherSelection->id, $ids);
    }

    public function test_cen_selfeval_004_student_index_exige_papel_aluno()
    {
        $this->createOpenTerm();
        $docente = $this->docente();

        $this->actingAs($docente)->get('/students/selfevaluations')->assertForbidden();
    }

    public function test_cen_selfeval_005_create_fora_da_janela_de_avaliacao_bloqueia()
    {
        $fixture = $this->envWithSelection(false);

        $this->from('/')->get($this->signedCreateUrl($fixture['selection']))->assertRedirect('/');
        $this->assertSessionHasWarningContaining('avaliação encerrado');
    }

    public function test_cen_selfeval_006_create_na_janela_de_avaliacao_por_aluno_dono()
    {
        $fixture = $this->envWithSelection();

        $resp = $this->actingAs($fixture['env']['aluno'])
            ->get('/selfevaluations/create?selectionID='.$fixture['selection']->id);

        $resp->assertOk();
    }

    public function test_cen_selfeval_007_create_via_url_assinada()
    {
        $fixture = $this->envWithSelection();

        // url assinada valida (sem autenticacao)
        $this->get($this->signedCreateUrl($fixture['selection']))->assertOk();

        // url inválida
        $this->get('/selfevaluations/create?selectionID='.$fixture['selection']->id.'&signature=invalida')->assertForbidden();

        // seleção inexistente (com assinatura válida)
        $this->from('/')->get(URL::signedRoute('selfevaluations.create', ['selectionID' => 999999]))->assertRedirect('/');
        $this->assertSessionHasWarningContaining('não encontrada');
    }

    public function test_cen_selfeval_008_create_por_aluno_cuja_selecao_nao_e_sua_negado()
    {
        $fixture = $this->envWithSelection();

        $otherStudent = $this->createStudent(['nompes' => 'Outro Aluno']);
        $otherClass = $this->createSchoolClass(['school_term_id' => $fixture['env']['term']->id, 'coddis' => 'MAC0202']);
        $this->createRequisition(['instructor_id' => $fixture['env']['instructor']->id, 'school_class_id' => $otherClass->id]);
        $otherEnrollment = $this->createEnrollment(['student_id' => $otherStudent->id, 'school_class_id' => $otherClass->id]);
        $otherSelection = $this->createSelection(['enrollment_id' => $otherEnrollment->id]);

        $this->from('/')->actingAs($fixture['env']['aluno'])
            ->get('/selfevaluations/create?selectionID='.$otherSelection->id)
            ->assertRedirect('/');
        $this->assertSessionHasWarningContaining('não pertence');
    }

    public function test_cen_selfeval_009_store_por_aluno_dono_cria_atualiza_a_autoavaliacao()
    {
        $fixture = $this->envWithSelection();

        $payload = $this->selfEvalPayload($fixture['selection']);

        $this->actingAs($fixture['env']['aluno'])->from('/students/selfevaluations')
            ->post('/selfevaluations', $payload)
            ->assertRedirect(route('selfevaluations.studentIndex'));

        $this->assertSame(1, SelfEvaluation::where('selection_id', $fixture['selection']->id)->count());

        // segundo envio atualiza o mesmo registro
        $this->actingAs($fixture['env']['aluno'])->from('/students/selfevaluations')
            ->post('/selfevaluations', array_merge($payload, ['comments' => 'atualizado']))
            ->assertRedirect(route('selfevaluations.studentIndex'));

        $this->assertSame(1, SelfEvaluation::where('selection_id', $fixture['selection']->id)->count());
        $this->assertSame('atualizado', SelfEvaluation::where('selection_id', $fixture['selection']->id)->first()->comments);
    }

    public function test_cen_selfeval_010_store_via_link_assinado_valida_hash_do_json_da_selecao()
    {
        $fixture = $this->envWithSelection();
        $selection = $fixture['selection'];
        $validHash = Hash::make(json_encode($selection->fresh()->toArray()));

        // hash válido
        $this->post('/selfevaluations', $this->selfEvalPayload($selection, $validHash))->assertRedirect('/');
        $this->assertSame(1, SelfEvaluation::where('selection_id', $selection->id)->count());

        // hash inválido
        $this->post('/selfevaluations', $this->selfEvalPayload($selection, 'hash-invalido'))->assertRedirect('/');
        $this->assertSame(1, SelfEvaluation::where('selection_id', $selection->id)->count());
        $this->assertSessionHasWarningContaining('não pertence');
    }

    public function test_cen_selfeval_011_store_exige_selecao_pertencer_ao_usuario_autenticado()
    {
        $fixture = $this->envWithSelection();

        $otherStudent = $this->createStudent(['nompes' => 'Outro Aluno']);
        $otherClass = $this->createSchoolClass(['school_term_id' => $fixture['env']['term']->id, 'coddis' => 'MAC0202']);
        $this->createRequisition(['instructor_id' => $fixture['env']['instructor']->id, 'school_class_id' => $otherClass->id]);
        $otherEnrollment = $this->createEnrollment(['student_id' => $otherStudent->id, 'school_class_id' => $otherClass->id]);
        $otherSelection = $this->createSelection(['enrollment_id' => $otherEnrollment->id]);

        $this->from('/')->actingAs($fixture['env']['aluno'])
            ->post('/selfevaluations', $this->selfEvalPayload($otherSelection))
            ->assertRedirect('/');
        $this->assertSessionHasWarningContaining('não pertence');
        $this->assertSame(0, SelfEvaluation::where('selection_id', $otherSelection->id)->count());
    }

    public function test_cen_selfeval_012_validacao_do_store()
    {
        $fixture = $this->envWithSelection();
        $selection = $fixture['selection'];

        $cases = [
            'selection_id_ausente' => ['selection_id' => null],
            'selection_hash_ausente' => ['selection_hash' => null],
            'student_amount_nao_inteiro' => ['student_amount' => 'x'],
            'homework_amount_nao_inteiro' => ['homework_amount' => 'x'],
            'workload_nao_inteiro' => ['workload' => 'x'],
        ];

        foreach ($cases as $key => $overrides) {
            $payload = array_merge($this->selfEvalPayload($selection), $overrides);
            $this->actingAs($fixture['env']['aluno'])->post('/selfevaluations', $payload)->assertSessionHasErrors();
        }

        // opcionais aceitos como texto (aluno dono autenticado pelo request anterior)
        $this->actingAs($fixture['env']['aluno'])->from('/students/selfevaluations')
            ->post('/selfevaluations', $this->selfEvalPayload($selection, Hash::make(json_encode($selection->fresh()->toArray())), 'ativ. extra', 'motivo', 'comentario'))
            ->assertRedirect(route('selfevaluations.studentIndex'));

        $this->assertSame(1, SelfEvaluation::where('selection_id', $selection->id)->count());
    }

    public function test_cen_selfeval_013_show_permite_aluno_dono_ou_permissao()
    {
        $fixture = $this->envWithSelection();
        $se = SelfEvaluation::create(['selection_id' => $fixture['selection']->id, 'student_amount' => 1, 'homework_amount' => 1, 'workload' => 1]);

        $this->actingAs($fixture['env']['aluno'])->get('/selfevaluations/'.$se->id)->assertOk();
        $this->actingAs($fixture['env']['secretaria'])->get('/selfevaluations/'.$se->id)->assertOk();

        $semPermissao = $this->createUser('Monitor');
        $this->actingAs($semPermissao)->get('/selfevaluations/'.$se->id)->assertForbidden();
    }

    public function test_cen_selfeval_014_edit_update_restrito_ao_aluno_dono()
    {
        $fixture = $this->envWithSelection();
        $se = SelfEvaluation::create(['selection_id' => $fixture['selection']->id, 'student_amount' => 1, 'homework_amount' => 1, 'workload' => 1]);

        // não-dono não pode acessar a edição
        $outside = $this->aluno();
        $this->from('/')->actingAs($outside)->get('/selfevaluations/'.$se->id.'/edit')->assertRedirect('/');
        $this->assertSessionHasWarningContaining('não pertence');

        // dono edita
        $this->from('/students/selfevaluations')->actingAs($fixture['env']['aluno'])
            ->put('/selfevaluations/'.$se->id, [
                'student_amount' => 3,
                'homework_amount' => 4,
                'workload' => 2,
            ])
            ->assertRedirect(route('selfevaluations.studentIndex'));

        $this->assertSame(3, (int) $se->fresh()->student_amount);
    }

    public function test_cen_selfeval_015_destroy_eh_stub_vazio()
    {
        $fixture = $this->envWithSelection();
        $se = SelfEvaluation::create(['selection_id' => $fixture['selection']->id, 'student_amount' => 1, 'homework_amount' => 1, 'workload' => 1]);

        $this->actingAs($fixture['env']['admin'])->delete('/selfevaluations/'.$se->id);

        $this->assertDatabaseHas('self_evaluations', ['id' => $se->id]);
    }

    // ------------------------------------------------------------------

    protected function selfEvalPayload(Selection $selection, ?string $hash = null, ?string $secondary = null, ?string $reason = null, ?string $comments = null): array
    {
        return [
            'selection_id' => $selection->id,
            'selection_hash' => $hash ?? Hash::make(json_encode($selection->toArray())),
            'student_amount' => 1,
            'homework_amount' => 2,
            'secondary_activity' => $secondary,
            'workload' => 1,
            'workload_reason' => $reason,
            'comments' => $comments,
        ];
    }
}