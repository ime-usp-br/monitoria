<?php

namespace Tests\Feature\Scenario;

use App\Models\Enrollment;
use App\Models\SchoolClass;
use App\Models\SchoolRecord;
use App\Models\SchoolTerm;
use App\Models\Scholarship;
use App\Models\Selection;
use App\Models\Student;

class EnrollmentScenarioTest extends ScenarioTestCase
{
    public function test_cen_enrollment_001_index_exige_papel_aluno()
    {
        $this->createOpenTerm();
        $docente = $this->docente();

        $this->actingAs($docente)->get('/enrollments')->assertForbidden();
    }

    public function test_cen_enrollment_002_index_fora_do_periodo_de_inscricoes_redireciona_com_aviso()
    {
        $aluno = $this->aluno();
        $this->createOpenTerm([
            'start_date_enrollments' => now()->subDays(30)->format('d/m/Y'),
            'end_date_enrollments' => now()->subDays(20)->format('d/m/Y'),
            'start_date_requisitions' => now()->subDays(30)->format('d/m/Y'),
            'end_date_requisitions' => now()->subDays(20)->format('d/m/Y'),
            'start_date_evaluations' => now()->subDays(30)->format('d/m/Y'),
            'end_date_evaluations' => now()->subDays(20)->format('d/m/Y'),
        ]);

        $this->from('/')->actingAs($aluno)->get('/enrollments')->assertRedirect('/');
        $this->assertSessionHasWarningContaining('inscrições encerrado');
    }

    public function test_cen_enrollment_003_index_sem_periodo_aberto_ou_divergente_bloqueia()
    {
        $aluno = $this->aluno();

        // sem período aberto (janela de inscrição ativa em período Fechado)
        $closedWithWindows = SchoolTerm::create(array_merge($this->openTermAttributes(), [
            'year' => (int) now()->format('Y') - 1,
            'period' => '1° Semestre',
            'status' => 'Fechado',
        ]));
        $this->from('/')->actingAs($aluno)->get('/enrollments')->assertRedirect('/');
        $this->assertSessionHasWarningContaining('fechado');

        // período aberto diverge do período em inscrições
        $nextYear = (int) now()->format('Y') + 1;
        SchoolTerm::create(array_merge($this->openTermAttributes(), [
            'year' => $nextYear,
            'period' => '2° Semestre',
            'status' => 'Fechado',
        ]));
        SchoolTerm::create(array_merge($this->openTermAttributes(), [
            'year' => $nextYear,
            'period' => '1° Semestre',
            'status' => 'Aberto',
            'start_date_enrollments' => now()->subDays(30)->format('d/m/Y'),
            'end_date_enrollments' => now()->subDays(20)->format('d/m/Y'),
            'start_date_requisitions' => now()->subDays(30)->format('d/m/Y'),
            'end_date_requisitions' => now()->subDays(20)->format('d/m/Y'),
            'start_date_evaluations' => now()->subDays(30)->format('d/m/Y'),
            'end_date_evaluations' => now()->subDays(20)->format('d/m/Y'),
        ]));

        $this->from('/')->actingAs($aluno)->get('/enrollments')->assertRedirect('/');
        $this->assertSessionHasWarningContaining('secretaria');
    }

    public function test_cen_enrollment_004_index_redireciona_para_envio_do_historico_quando_nao_enviado()
    {
        $env = $this->seedEnvironmentWithoutRecord();
        $term = $env['term'];

        SchoolRecord::where('student_id', $env['student']->id)->delete();

        $this->actingAs($env['aluno'])->get('/enrollments')->assertRedirect(route('schoolRecords.create'));
    }

    public function test_cen_enrollment_005_index_lista_turmas_inscritas_primeiro_e_depois_as_demais()
    {
        $env = $this->seedEnvironment();

        $t2 = $this->createSchoolClass(['school_term_id' => $env['term']->id, 'coddis' => 'MAC0202']);
        $t3 = $this->createSchoolClass(['school_term_id' => $env['term']->id, 'coddis' => 'MAC0103']);

        $resp = $this->actingAs($env['aluno'])->get('/enrollments');

        $resp->assertOk();
        $turmas = $resp->viewData('turmas');
        $ids = $turmas->pluck('id')->all();
        $coddis = $turmas->map->coddis->values()->all();

        // turma já inscrita aparece primeiro
        $this->assertSame($env['class']->id, $ids[0]);
        $this->assertSame('MAC0101', $coddis[0]);

        // demais turmas ordenadas por coddis
        $rest = array_slice($coddis, 1);
        $this->assertEquals(collect($rest)->sort()->values()->all(), $rest);
    }

    public function test_cen_enrollment_006_guarda_de_maximo_de_inscricoes()
    {
        $env = $this->seedEnvironment(['max_enrollments' => 2, 'enrollment' => false]);
        $term = $env['term'];

        $c1 = $env['class'];
        $c2 = $this->createSchoolClass(['school_term_id' => $term->id, 'coddis' => 'MAC0202']);
        $c3 = $this->createSchoolClass(['school_term_id' => $term->id, 'coddis' => 'MAC0303']);

        $this->createEnrollment(['student_id' => $env['student']->id, 'school_class_id' => $c1->id]);
        $this->createEnrollment(['student_id' => $env['student']->id, 'school_class_id' => $c2->id]);

        $this->from('/enrollments')->actingAs($env['aluno'])
            ->get('/enrollments/create?school_class_id='.$c3->id)
            ->assertRedirect('/enrollments');
        $this->assertSessionHasWarningContaining('máximo de inscrições');
    }

    public function test_cen_enrollment_007_guarda_de_maximo_respeitada_no_limite()
    {
        $env = $this->seedEnvironment(['max_enrollments' => 2, 'enrollment' => false]);
        $term = $env['term'];

        $c1 = $env['class'];
        $this->createEnrollment(['student_id' => $env['student']->id, 'school_class_id' => $c1->id]);

        $resp = $this->actingAs($env['aluno'])->get('/enrollments/create?school_class_id='.$c1->id);
        $resp->assertOk();
    }

    public function test_cen_enrollment_008_store_inscreve_em_todas_as_turmas_da_mesma_coddis()
    {
        $env = $this->seedEnvironment(['enrollment' => false]);
        $term = $env['term'];

        $env['class']->update(['coddis' => 'MAC0110']);
        $t2 = $this->createSchoolClass(['school_term_id' => $term->id, 'coddis' => 'MAC0110']);
        $t3 = $this->createSchoolClass(['school_term_id' => $term->id, 'coddis' => 'MAC0110']);

        $scholarship = Scholarship::first();

        $this->actingAs($env['aluno'])->from('/enrollments')
            ->post('/enrollments', [
                'school_class_id' => $env['class']->id,
                'voluntario' => 1,
                'disponibilidade_diurno' => 1,
                'disponibilidade_noturno' => 1,
                'preferencia_horario' => 'Manhã',
                'observacoes' => 'quero monitoria',
                'scholarships' => [$scholarship->id],
            ])
            ->assertRedirect('/enrollments');

        $studentId = Student::where('codpes', $env['aluno']->codpes)->first()->id;
        $enrollments = Enrollment::where('student_id', $studentId)->get();
        $this->assertCount(3, $enrollments);
        $this->assertSame(['MAC0110', 'MAC0110', 'MAC0110'], $enrollments->map->schoolclass->map->coddis->all());
        foreach ($enrollments as $enrollment) {
            $this->assertSame([$scholarship->id], $enrollment->others_scholarships()->pluck('scholarships.id')->all());
        }
    }

    public function test_cen_enrollment_009_store_persiste_preferencias_e_observacoes()
    {
        $env = $this->seedEnvironment(['enrollment' => false]);

        $this->actingAs($env['aluno'])->from('/enrollments')
            ->post('/enrollments', [
                'school_class_id' => $env['class']->id,
                'voluntario' => 1,
                'disponibilidade_diurno' => 0,
                'disponibilidade_noturno' => 1,
                'preferencia_horario' => 'Noturno',
                'observacoes' => 'prefiro segunda a noite',
            ])
            ->assertRedirect('/enrollments');

        $enrollment = Enrollment::where('school_class_id', $env['class']->id)->first();
        $this->assertSame(1, (int) $enrollment->voluntario);
        $this->assertSame(0, (int) $enrollment->disponibilidade_diurno);
        $this->assertSame(1, (int) $enrollment->disponibilidade_noturno);
        $this->assertSame('Noturno', $enrollment->preferencia_horario);
        $this->assertSame('prefiro segunda a noite', $enrollment->observacoes);
    }

    public function test_cen_enrollment_010_validacao_do_formulario_de_criacao()
    {
        $env = $this->seedEnvironment();
        $scholarship = Scholarship::first();

        $cases = [
            'school_class_id_nao_numerico' => ['school_class_id' => 'abc'],
            'preferencia_horario_ausente' => ['preferencia_horario' => null],
            'observacoes_grande' => ['preferencia_horario' => 'Manhã', 'observacoes' => str_repeat('a', 65501)],
            'bolsa_inexistente' => ['preferencia_horario' => 'Manhã', 'scholarships' => [999999]],
        ];

        foreach ($cases as $key => $overrides) {
            $payload = array_merge([
                'school_class_id' => $env['class']->id,
                'preferencia_horario' => 'Manhã',
            ], $overrides);
            $this->actingAs($env['aluno'])->post('/enrollments', $payload)->assertSessionHasErrors();
        }
    }

    public function test_cen_enrollment_011_update_propaga_para_todas_as_inscricoes_da_mesma_coddis()
    {
        $env = $this->seedEnvironment(['enrollment' => false]);
        $term = $env['term'];

        $env['class']->update(['coddis' => 'MAC0110']);
        $t2 = $this->createSchoolClass(['school_term_id' => $term->id, 'coddis' => 'MAC0110']);

        $studentId = Student::where('codpes', $env['aluno']->codpes)->first()->id;
        $e1 = $this->createEnrollment(['student_id' => $studentId, 'school_class_id' => $env['class']->id]);
        $e2 = $this->createEnrollment(['student_id' => $studentId, 'school_class_id' => $t2->id]);

        $s1 = Scholarship::first();

        $this->actingAs($env['aluno'])->from('/enrollments')
            ->put('/enrollments/'.$e1->id, [
                'disponibilidade_diurno' => 1,
                'preferencia_horario' => 'Indiferente',
                'scholarships' => [$s1->id],
            ])
            ->assertRedirect('/enrollments');

        $e1->refresh();
        $e2->refresh();
        $this->assertSame(0, (int) $e1->voluntario);
        $this->assertSame(1, (int) $e1->disponibilidade_diurno);
        $this->assertSame(0, (int) $e1->disponibilidade_noturno);
        $this->assertSame('Indiferente', $e1->preferencia_horario);
        $this->assertSame($e1->preferencia_horario, $e2->preferencia_horario);
        $this->assertSame([$s1->id], $e1->others_scholarships()->pluck('scholarships.id')->all());
        $this->assertSame([$s1->id], $e2->others_scholarships()->pluck('scholarships.id')->all());
    }

    public function test_cen_enrollment_012_update_normaliza_booleanos()
    {
        $env = $this->seedEnvironment(['enrollment' => false]);
        $studentId = Student::where('codpes', $env['aluno']->codpes)->first()->id;
        $e1 = $this->createEnrollment(['student_id' => $studentId, 'school_class_id' => $env['class']->id]);

        // payload sem os booleanos
        $this->actingAs($env['aluno'])->from('/enrollments')
            ->put('/enrollments/'.$e1->id, ['preferencia_horario' => 'Manhã'])
            ->assertRedirect('/enrollments');

        $e1->refresh();
        $this->assertSame(0, (int) $e1->voluntario);
        $this->assertSame(0, (int) $e1->disponibilidade_diurno);
        $this->assertSame(0, (int) $e1->disponibilidade_noturno);
    }

    public function test_cen_enrollment_013_delete_bloqueado_quando_ha_selecao_na_disciplina()
    {
        $env = $this->seedEnvironment();
        $enrollment = $env['enrollment'];

        $this->createSelection(['enrollment_id' => $enrollment->id]);

        $this->from('/enrollments')->actingAs($env['aluno'])
            ->delete('/enrollments/'.$enrollment->id)
            ->assertRedirect();

        $this->assertSessionHasWarningContaining('selecionado');
        $this->assertDatabaseHas('enrollments', ['id' => $enrollment->id]);
    }

    public function test_cen_enrollment_014_delete_exclui_todas_as_inscricoes_da_mesma_coddis()
    {
        $env = $this->seedEnvironment(['enrollment' => false]);
        $term = $env['term'];

        $env['class']->update(['coddis' => 'MAC0110']);
        $t2 = $this->createSchoolClass(['school_term_id' => $term->id, 'coddis' => 'MAC0110']);

        $studentId = Student::where('codpes', $env['aluno']->codpes)->first()->id;
        $e1 = $this->createEnrollment(['student_id' => $studentId, 'school_class_id' => $env['class']->id]);
        $e2 = $this->createEnrollment(['student_id' => $studentId, 'school_class_id' => $t2->id]);

        $this->actingAs($env['aluno'])->from('/enrollments')
            ->delete('/enrollments/'.$e1->id)
            ->assertRedirect('/enrollments');

        $this->assertDatabaseMissing('enrollments', ['id' => $e1->id]);
        $this->assertDatabaseMissing('enrollments', ['id' => $e2->id]);
    }

    public function test_cen_enrollment_015_showall_lista_alunos_inscritos_no_periodo()
    {
        $env = $this->seedEnvironment();
        $term = $env['term'];

        $student2 = $this->createStudent(['nompes' => 'Zezinho']);
        $aluno2 = $this->aluno(['codpes' => $student2->codpes], $student2);
        $this->createSchoolRecord($student2, $term);
        $this->createEnrollment(['student_id' => $student2->id, 'school_class_id' => $env['class_mat']->id]);

        $resp = $this->actingAs($env['secretaria'])->get('/enrollments/showAll?periodoId='.$term->id);

        $resp->assertOk();
        $alunos = $resp->viewData('alunos');
        $names = $alunos->pluck('nompes')->all();
        $expected = collect($names)->sort()->values()->all();
        $this->assertSame($expected, $names);
    }

    public function test_cen_enrollment_016_showall_sem_permissao_e_negado()
    {
        $env = $this->seedEnvironment();
        $docente = $this->docente();

        $this->actingAs($docente)->get('/enrollments/showAll')->assertForbidden();
    }

    // ------------------------------------------------------------------

    protected function seedEnvironmentWithoutRecord(array $options = [])
    {
        $env = $this->seedEnvironment($options);

        return $env;
    }
}