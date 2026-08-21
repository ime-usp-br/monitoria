<?php

namespace Tests\Feature\Scenario;

use App\Models\Activity;
use App\Models\Instructor;
use App\Models\Recommendation;
use App\Models\Requisition;
use App\Models\SchoolClass;
use App\Models\SchoolTerm;
use App\Models\Student;

class RequisitionScenarioTest extends ScenarioTestCase
{
    public function test_cen_requisition_001_acesso_ao_index_requer_papel_docente()
    {
        $this->createOpenTerm();
        $monitor = $this->createUser('Monitor');

        $this->actingAs($monitor)->get('/requisitions')->assertForbidden();
    }

    public function test_cen_requisition_002_index_com_periodo_de_solicitacao_inativo_redireciona_com_aviso()
    {
        $docente = $this->docente();

        $this->createOpenTerm([
            'start_date_requisitions' => now()->subDays(30)->format('d/m/Y'),
            'end_date_requisitions' => now()->subDays(20)->format('d/m/Y'),
            'start_date_enrollments' => now()->subDays(30)->format('d/m/Y'),
            'end_date_enrollments' => now()->subDays(20)->format('d/m/Y'),
            'start_date_evaluations' => now()->subDays(30)->format('d/m/Y'),
            'end_date_evaluations' => now()->subDays(20)->format('d/m/Y'),
        ]);

        $this->from('/')->actingAs($docente)->get('/requisitions')->assertRedirect('/');
        $this->assertSessionHasWarningContaining('solicitação');
    }

    public function test_cen_requisition_003_index_sem_periodo_aberto_redireciona_com_aviso()
    {
        $docente = $this->docente();
        $this->createClosedTerm();

        $this->from('/')->actingAs($docente)->get('/requisitions')->assertRedirect('/');
        $this->assertSessionHasWarningContaining('fechado');
    }

    public function test_cen_requisition_004_index_com_periodo_aberto_divergente_avisa_a_secretaria()
    {
        $docente = $this->docente();

        // Open term WITHOUT active requisition window
        $this->createOpenTerm([
            'start_date_requisitions' => now()->subDays(30)->format('d/m/Y'),
            'end_date_requisitions' => now()->subDays(20)->format('d/m/Y'),
            'start_date_enrollments' => now()->subDays(30)->format('d/m/Y'),
            'end_date_enrollments' => now()->subDays(20)->format('d/m/Y'),
            'start_date_evaluations' => now()->subDays(30)->format('d/m/Y'),
            'end_date_evaluations' => now()->subDays(20)->format('d/m/Y'),
        ]);
        // Closed term with the ACTIVE requisition window
        SchoolTerm::create(array_merge($this->openTermAttributes(), [
            'year' => (int) now()->format('Y') + 1,
            'period' => '2° Semestre',
            'status' => 'Fechado',
        ]));

        $this->from('/')->actingAs($docente)->get('/requisitions')->assertRedirect('/');
        $this->assertSessionHasWarningContaining('secretaria');
    }

    public function test_cen_requisition_005_index_lista_as_turmas_do_docente_no_periodo_de_solicitacao()
    {
        $env = $this->seedEnvironment();

        $other = $this->createInstructor();
        $t3 = $this->createSchoolClass([
            'school_term_id' => $env['term']->id,
            'instructors' => [$other->id],
        ]);

        $resp = $this->actingAs($env['docente'])->get('/requisitions');

        $resp->assertOk();
        $turmas = $resp->viewData('turmas');
        $this->assertTrue($turmas->contains('id', $env['class']->id));
        $this->assertFalse($turmas->contains('id', $t3->id));
    }

    public function test_cen_requisition_006_create_exige_ser_instrutor_da_turma()
    {
        $env = $this->seedEnvironment();

        // docente2 is not instructor of env['class']
        $docente2 = $this->docente();

        $this->actingAs($docente2)
            ->get('/requisitions/create?school_class_id='.$env['class']->id)
            ->assertForbidden();
    }

    public function test_cen_requisition_007_create_exige_que_a_turma_esteja_no_periodo_de_solicitacao()
    {
        $env = $this->seedEnvironment();

        $pastTerm = SchoolTerm::create(array_merge($this->openTermAttributes(), [
            'year' => (int) now()->format('Y') + 5,
            'status' => 'Fechado',
            'start_date_requisitions' => now()->subDays(30)->format('d/m/Y'),
            'end_date_requisitions' => now()->subDays(20)->format('d/m/Y'),
        ]));

        $pastClass = $this->createSchoolClass([
            'school_term_id' => $pastTerm->id,
            'instructors' => [$env['instructor']->id],
            'codtur' => '2031101',
            'coddis' => 'MAC0909',
        ]);

        $this->from('/requisitions')->actingAs($env['docente'])
            ->get('/requisitions/create?school_class_id='.$pastClass->id)
            ->assertRedirect('/requisitions');
        $this->assertSessionHasWarningContaining('encerrado');
    }

    public function test_cen_requisition_008_store_cria_solicitacao_com_instrutor_do_usuario_autenticado()
    {
        $env = $this->seedEnvironment();
        $class = $this->freshClass($env);

        $this->actingAs($env['docente'])->from('/requisitions')
            ->post('/requisitions', array_merge($this->storePayload($class), [
                'requested_number' => 2,
                'priority' => 1,
                'comments' => 'preciso de ajuda',
            ]))
            ->assertRedirect('/requisitions');

        $this->assertDatabaseHas('requisitions', [
            'instructor_id' => $env['instructor']->id,
            'school_class_id' => $class->id,
            'requested_number' => 2,
            'priority' => '1',
            'comments' => 'preciso de ajuda',
        ]);
    }

    public function test_cen_requisition_009_store_anexa_atividades_padrao()
    {
        $env = $this->seedEnvironment();
        $class = $this->freshClass($env);
        $before = Activity::count();

        $this->actingAs($env['docente'])->from('/requisitions')
            ->post('/requisitions', $this->storePayload($class, ['Atendimento a alunos', 'Correção de listas de exercícios']))
            ->assertRedirect('/requisitions');

        $this->assertSame($before, Activity::count());
        $requisition = Requisition::where('school_class_id', $class->id)->first();
        $this->assertNotNull($requisition);
        $this->assertSame(2, $requisition->activities()->count());
        $this->assertDatabaseHas('activity_requisition', ['requisition_id' => $requisition->id]);
    }

    public function test_cen_requisition_010_store_cria_recomendacoes_atualizando_alunos_do_replicado()
    {
        $env = $this->seedEnvironment();
        $class = $this->freshClass($env);
        $codpes = mt_rand(10000000, 99999999);

        $this->actingAs($env['docente'])->from('/requisitions')
            ->post('/requisitions', array_merge($this->storePayload($class), [
                'recommendations' => [['codpes' => (string) $codpes]],
            ]))
            ->assertRedirect('/requisitions');

        $this->assertDatabaseHas('students', ['codpes' => (string) $codpes]);
        $this->assertDatabaseHas('recommendations', [
            'requisition_id' => Requisition::where('school_class_id', $class->id)->first()->id,
            'student_id' => Student::where('codpes', $codpes)->first()->id,
        ]);
    }

    public function test_cen_requisition_011_store_anexa_bolsas_externas()
    {
        $env = $this->seedEnvironment();
        $class = $this->freshClass($env);
        $scholarship = \App\Models\Scholarship::first();

        $this->actingAs($env['docente'])->from('/requisitions')
            ->post('/requisitions', array_merge($this->storePayload($class), [
                'scholarships' => [$scholarship->id],
            ]))
            ->assertRedirect('/requisitions');

        $this->assertDatabaseHas('model_has_scholarships', [
            'scholarship_id' => $scholarship->id,
            'model_id' => Requisition::where('school_class_id', $class->id)->first()->id,
            'model_type' => Requisition::class,
        ]);
    }

    public function test_cen_requisition_012_validacao_do_formulario()
    {
        $env = $this->seedEnvironment();
        $payload = $this->storePayload($env['class']);
        $scholarship = \App\Models\Scholarship::first();

        $cases = [
            'school_class_id_nao_numerico' => ['school_class_id' => 'x'],
            'requested_number_zero' => ['requested_number' => 0],
            'requested_number_nao_numerico' => ['requested_number' => 'x'],
            'priority_fora' => ['priority' => 9],
            'codpes_recomendacao_nao_numerico' => ['recommendations' => [['codpes' => 'x']]],
            'atividade_fora' => ['activities' => ['Jardinagem']],
            'bolsa_inexistente' => ['scholarships' => [999999]],
        ];

        foreach ($cases as $key => $overrides) {
            $this->actingAs($env['docente'])->post('/requisitions', array_merge($payload, $overrides))->assertSessionHasErrors();
        }
    }

    public function test_cen_requisition_013_update_substitui_atividades()
    {
        $env = $this->seedEnvironment();
        $class = $this->freshClass($env);

        $requisition = $this->createRequisition([
            'instructor_id' => $env['instructor']->id,
            'school_class_id' => $class->id,
        ]);
        $requisition->activities()->attach(Activity::where('description', 'Atendimento a alunos')->first()->id);
        $requisition->activities()->attach(Activity::where('description', 'Correção de listas de exercícios')->first()->id);

        $this->actingAs($env['docente'])->from('/requisitions')
            ->put('/requisitions/'.$requisition->id, $this->updatePayload(['Fiscalização de provas']))
            ->assertRedirect('/requisitions');

        $this->assertFalse($requisition->fresh()->hasActivity('Atendimento a alunos'));
        $this->assertTrue($requisition->fresh()->hasActivity('Fiscalização de provas'));
    }

    public function test_cen_requisition_014_update_exclui_e_recria_recomendacoes()
    {
        $env = $this->seedEnvironment();
        $class = $this->freshClass($env);

        $studentA = $this->createStudent(['codpes' => 11111111]);
        $studentB = $this->createStudent(['codpes' => 22222222]);

        $requisition = $this->createRequisition([
            'instructor_id' => $env['instructor']->id,
            'school_class_id' => $class->id,
        ]);
        Recommendation::create(['student_id' => $studentA->id, 'requisition_id' => $requisition->id]);
        Recommendation::create(['student_id' => $studentB->id, 'requisition_id' => $requisition->id]);

        $newCodpes = 33333333;

        $this->actingAs($env['docente'])->from('/requisitions')
            ->put('/requisitions/'.$requisition->id, $this->updatePayload(['Atendimento a alunos'], [['codpes' => $newCodpes]]))
            ->assertRedirect('/requisitions');

        $this->assertSame(1, Recommendation::where('requisition_id', $requisition->id)->count());
        $this->assertDatabaseHas('students', ['codpes' => $newCodpes]);
    }

    public function test_cen_requisition_015_update_ressincroniza_bolsas()
    {
        $env = $this->seedEnvironment();
        $class = $this->freshClass($env);
        $s1 = \App\Models\Scholarship::first();
        $s2 = \App\Models\Scholarship::skip(1)->first();

        $requisition = $this->createRequisition([
            'instructor_id' => $env['instructor']->id,
            'school_class_id' => $class->id,
        ]);
        $requisition->others_scholarships()->attach($s1);
        $requisition->others_scholarships()->attach($s2);

        $this->actingAs($env['docente'])->from('/requisitions')
            ->put('/requisitions/'.$requisition->id, array_merge(
                $this->updatePayload(['Atendimento a alunos']),
                ['scholarships' => [$s1->id]]
            ))
            ->assertRedirect('/requisitions');

        $requisition->refresh();
        $this->assertSame([$s1->id], $requisition->others_scholarships()->pluck('scholarships.id')->all());
    }

    public function test_cen_requisition_016_edit_exige_turma_propria_e_periodo_de_solicitacao()
    {
        $env = $this->seedEnvironment();

        $other = $this->createInstructor();
        $otherClass = $this->createSchoolClass([
            'school_term_id' => $env['term']->id,
            'instructors' => [$other->id],
            'coddis' => 'MAC0777',
        ]);
        $otherRequisition = $this->createRequisition([
            'instructor_id' => $other->id,
            'school_class_id' => $otherClass->id,
        ]);

        // not the instructor of the requisition's class
        $this->actingAs($env['docente'])
            ->get('/requisitions/'.$otherRequisition->id.'/edit')
            ->assertForbidden();

        // own class but window in the past
        $pastTerm = SchoolTerm::create(array_merge($this->openTermAttributes(), [
            'year' => (int) now()->format('Y') + 5,
            'status' => 'Fechado',
            'start_date_requisitions' => now()->subDays(30)->format('d/m/Y'),
            'end_date_requisitions' => now()->subDays(20)->format('d/m/Y'),
        ]));
        $pastClass = $this->createSchoolClass([
            'school_term_id' => $pastTerm->id,
            'instructors' => [$env['instructor']->id],
            'coddis' => 'MAC0778',
        ]);
        $pastRequisition = $this->createRequisition([
            'instructor_id' => $env['instructor']->id,
            'school_class_id' => $pastClass->id,
        ]);

        $this->from('/requisitions')->actingAs($env['docente'])
            ->get('/requisitions/'.$pastRequisition->id.'/edit')
            ->assertRedirect('/requisitions');
        $this->assertSessionHasWarningContaining('encerrado');
    }

    public function test_cen_requisition_017_destroy_eh_stub_vazio()
    {
        $env = $this->seedEnvironment();
        $class = $this->freshClass($env);
        $requisition = $this->createRequisition([
            'instructor_id' => $env['instructor']->id,
            'school_class_id' => $class->id,
        ]);

        $this->actingAs($env['docente'])->delete('/requisitions/'.$requisition->id);

        $this->assertDatabaseHas('requisitions', ['id' => $requisition->id]);
    }

    public function test_cen_requisition_018_assinatura_de_instrutor_correta_na_criacao()
    {
        $env = $this->seedEnvironment();
        $class = $this->freshClass($env);

        $this->actingAs($env['docente'])->from('/requisitions')
            ->post('/requisitions', array_merge($this->storePayload($class), [
                'instructor_id' => $this->createInstructor()->id,
            ]))
            ->assertRedirect('/requisitions');

        $requisition = Requisition::first();
        $this->assertSame($env['instructor']->id, $requisition->instructor_id);
    }

    // ------------------------------------------------------------------

    protected function freshClass(array $env): SchoolClass
    {
        return $this->createSchoolClass([
            'school_term_id' => $env['term']->id,
            'instructors' => [$env['instructor']->id],
            'coddis' => 'MAC'.mt_rand(200, 999),
        ]);
    }

    protected function storePayload(SchoolClass $class, array $activities = ['Atendimento a alunos']): array
    {
        return [
            'school_class_id' => $class->id,
            'requested_number' => 1,
            'priority' => 1,
            'activities' => $activities,
        ];
    }

    protected function updatePayload(array $activities = ['Atendimento a alunos'], array $recommendations = []): array
    {
        return [
            'requested_number' => 1,
            'priority' => 2,
            'activities' => $activities,
            'recommendations' => $recommendations,
        ];
    }
}