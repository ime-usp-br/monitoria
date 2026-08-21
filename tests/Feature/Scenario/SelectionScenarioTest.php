<?php

namespace Tests\Feature\Scenario;

use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Frequency;
use App\Models\InstructorEvaluation;
use App\Models\SchoolClass;
use App\Models\SchoolRecord;
use App\Models\SchoolTerm;
use App\Models\Selection;
use App\Models\SelfEvaluation;
use App\Models\Student;

class SelectionScenarioTest extends ScenarioTestCase
{
    public function test_cen_selection_001_index_exige_periodo_aberto_e_permissao()
    {
        $secretaria = $this->secretaria();

        // sem período aberto
        $this->createClosedTerm();
        $this->from('/selections')->actingAs($secretaria)->get('/selections')->assertRedirect();
        $this->assertSessionHasWarningContaining('aberto');

        // sem permissão
        $this->createOpenTerm();
        $docente = $this->docente();
        $this->actingAs($docente)->get('/selections')->assertForbidden();
    }

    public function test_cen_selection_002_index_secretaria_admin_presidente_lista_todas_solicitacoes()
    {
        $env = $this->seedEnvironment();

        foreach (['secretaria', 'admin'] as $role) {
            $resp = $this->actingAs($env[$role])->get('/selections');
            $resp->assertOk();
            $this->assertCount(2, $resp->viewData('solicitacoes'));
        }
    }

    public function test_cen_selection_003_index_membro_comissao_lista_apenas_proprio_departamento()
    {
        $env = $this->seedEnvironment();

        $resp = $this->actingAs($env['membro'])->get('/selections');
        $resp->assertOk();
        $solicitacoes = $resp->viewData('solicitacoes');
        $this->assertCount(1, $solicitacoes);
        $this->assertSame($env['class']->id, $solicitacoes->first()->school_class_id);
    }

    public function test_cen_selection_004_store_com_inscricao_ja_eleita_deseleciona_a_selecao_anterior()
    {
        $env = $this->seedEnvironment();
        $enrollment = $env['enrollment'];

        // create a previous selection with dependent records
        $previous = $this->createSelection(['enrollment_id' => $enrollment->id]);
        Frequency::createFromSelection($previous);
        SelfEvaluation::create([
            'selection_id' => $previous->id,
            'student_amount' => 1,
            'homework_amount' => 1,
            'workload' => 1,
        ]);
        InstructorEvaluation::create([
            'selection_id' => $previous->id,
            'ease_of_contact' => 0,
            'efficiency' => 0,
            'reliability' => 0,
            'overall' => 0,
        ]);

        $this->actingAs($env['secretaria'])->from('/selections')
            ->post('/selections', ['enrollment_id' => $enrollment->id])
            ->assertRedirect();

        // previous selection and dependent records removed; new selection gets fresh frequencies
        $this->assertDatabaseMissing('selections', ['id' => $previous->id]);
        $this->assertSame(0, SelfEvaluation::where('selection_id', $previous->id)->count());
        $this->assertSame(0, InstructorEvaluation::where('selection_id', $previous->id)->count());
        $this->assertSame([3, 4, 5, 6], Frequency::where('student_id', $previous->student_id)
            ->where('school_class_id', $previous->school_class_id)
            ->pluck('month')->sort()->values()->all());

        // new selection created
        $this->assertDatabaseHas('selections', [
            'student_id' => $enrollment->student_id,
            'school_class_id' => $enrollment->school_class_id,
            'sitatl' => 'Ativo',
            'codpescad' => $env['secretaria']->codpes,
        ]);
    }

    public function test_cen_selection_005_store_preenche_student_school_class_requisition_corretamente()
    {
        $env = $this->seedEnvironment();
        $enrollment = $env['enrollment'];

        $this->actingAs($env['secretaria'])->from('/selections')
            ->post('/selections', ['enrollment_id' => $enrollment->id])
            ->assertRedirect();

        $selection = Selection::first();
        $this->assertSame($enrollment->student_id, $selection->student_id);
        $this->assertSame($enrollment->school_class_id, $selection->school_class_id);
        $this->assertSame($enrollment->schoolclass->requisition->id, $selection->requisition_id);
        $this->assertSame('Ativo', $selection->sitatl);
    }

    public function test_cen_selection_006_store_repopula_o_curso_do_aluno_no_periodo()
    {
        $env = $this->seedEnvironment();
        $enrollment = $env['enrollment'];

        $this->actingAs($env['secretaria'])->from('/selections')
            ->post('/selections', ['enrollment_id' => $enrollment->id])
            ->assertRedirect();

        $this->assertDatabaseHas('courses', [
            'student_id' => $enrollment->student_id,
            'schoolterm_id' => $env['term']->id,
            'nomcur' => 'Bacharelado em Ciencia da Computacao',
            'sglund' => 'IME',
        ]);
    }

    public function test_cen_selection_007_bloqueio_aluno_ja_eleito_em_outra_turma_do_periodo_aberto()
    {
        $env = $this->seedEnvironment();
        $enrollment = $env['enrollment'];

        $t2 = $this->createSchoolClass(['school_term_id' => $env['term']->id, 'coddis' => 'MAC0202']);
        $this->createRequisition(['instructor_id' => $env['instructor']->id, 'school_class_id' => $t2->id]);
        $enrollment2 = $this->createEnrollment(['student_id' => $env['student']->id, 'school_class_id' => $t2->id]);
        $this->createSelection(['enrollment_id' => $enrollment2->id]);

        $this->from('/selections')->actingAs($env['secretaria'])
            ->post('/selections', ['enrollment_id' => $enrollment->id])
            ->assertRedirect();

        $this->assertSessionHasWarningContaining('outra turma');
        $this->assertSame(1, Selection::count());
    }

    public function test_cen_selection_008_membro_comissao_so_seleciona_no_proprio_departamento()
    {
        $env = $this->seedEnvironment();

        // MAT enrollment
        $studentMat = $this->createStudent(['nompes' => 'Aluno Do Mat']);
        $this->createSchoolRecord($studentMat, $env['term']);
        $enrollmentMat = $this->createEnrollment(['student_id' => $studentMat->id, 'school_class_id' => $env['class_mat']->id]);

        // membro (MAC) cannot select in MAT
        $this->actingAs($env['membro'])->from('/selections')
            ->post('/selections', ['enrollment_id' => $enrollmentMat->id])
            ->assertForbidden();

        // membro (MAT) can select in MAT
        $this->actingAs($env['membro_mat'])->from('/selections')
            ->post('/selections', ['enrollment_id' => $enrollmentMat->id])
            ->assertRedirect();
        $this->assertDatabaseHas('selections', ['enrollment_id' => $enrollmentMat->id, 'sitatl' => 'Ativo']);
    }

    public function test_cen_selection_009_store_cria_frequencias_para_meses_ativos_do_periodo()
    {
        $env = $this->seedEnvironment();
        $enrollment = $env['enrollment'];

        $this->actingAs($env['secretaria'])->from('/selections')
            ->post('/selections', ['enrollment_id' => $enrollment->id])
            ->assertRedirect();

        $selection = Selection::first();
        $months = Frequency::where('student_id', $selection->student_id)
            ->where('school_class_id', $selection->school_class_id)
            ->pluck('month')->sort()->values()->all();
        $this->assertSame([3, 4, 5, 6], $months);

        // 2° Semestre -> months 8-11
        $term2 = SchoolTerm::create(array_merge($this->openTermAttributes(), [
            'year' => (int) now()->format('Y'),
            'period' => '2° Semestre',
        ]));
        $class2 = $this->createSchoolClass([
            'school_term_id' => $term2->id,
            'coddis' => 'MAC0202',
            'codtur' => '2026201',
            'instructors' => [$env['instructor']->id],
        ]);
        $req2 = $this->createRequisition(['instructor_id' => $env['instructor']->id, 'school_class_id' => $class2->id]);
        $student2 = $this->createStudent(['nompes' => 'Outro Aluno']);
        $this->createSchoolRecord($student2, $term2);
        $enrollment2 = $this->createEnrollment(['student_id' => $student2->id, 'school_class_id' => $class2->id]);

        $this->from('/selections')->actingAs($env['secretaria'])
            ->post('/selections', ['enrollment_id' => $enrollment2->id])
            ->assertRedirect();

        $selection2 = Selection::where('enrollment_id', $enrollment2->id)->first();
        $months2 = Frequency::where('student_id', $selection2->student_id)
            ->where('school_class_id', $selection2->school_class_id)
            ->pluck('month')->sort()->values()->all();
        $this->assertSame([8, 9, 10, 11], $months2);
    }

    public function test_cen_selection_010_store_usa_first_or_create_sem_duplicidade()
    {
        $env = $this->seedEnvironment();
        $enrollment = $env['enrollment'];

        $this->actingAs($env['secretaria'])->from('/selections')
            ->post('/selections', ['enrollment_id' => $enrollment->id])
            ->assertRedirect();

        $this->actingAs($env['secretaria'])->from('/selections')
            ->post('/selections', ['enrollment_id' => $enrollment->id])
            ->assertRedirect();

        $this->assertSame(1, Selection::count());
    }

    public function test_cen_selection_011_destroy_pretir_blocked_com_frequencia_registrada()
    {
        $env = $this->seedEnvironment();
        $selection = $this->createSelection(['enrollment_id' => $env['enrollment']->id]);
        Frequency::create([
            'student_id' => $selection->student_id,
            'school_class_id' => $selection->school_class_id,
            'month' => 3,
            'registered' => true,
        ]);

        $this->from('/selections')->actingAs($env['secretaria'])
            ->delete('/selections/'.$selection->id)
            ->assertRedirect();

        $this->assertSessionHasWarningContaining('presença');
        $this->assertDatabaseHas('selections', ['id' => $selection->id]);
    }

    public function test_cen_selection_012_destroy_sem_frequencias_registradas_exclui_selecao_e_frequencias()
    {
        $env = $this->seedEnvironment();
        $selection = $this->createSelection(['enrollment_id' => $env['enrollment']->id]);
        Frequency::create([
            'student_id' => $selection->student_id,
            'school_class_id' => $selection->school_class_id,
            'month' => 3,
            'registered' => false,
        ]);

        $this->from('/selections')->actingAs($env['secretaria'])
            ->delete('/selections/'.$selection->id)
            ->assertRedirect();

        $this->assertDatabaseMissing('selections', ['id' => $selection->id]);
        $this->assertDatabaseMissing('frequencies', ['school_class_id' => $selection->school_class_id, 'student_id' => $selection->student_id]);
    }

    public function test_cen_selection_013_destroy_membro_comissao_restrito_ao_departamento()
    {
        $env = $this->seedEnvironment();

        $studentMat = $this->createStudent(['nompes' => 'Aluno Do Mat']);
        $this->createSchoolRecord($studentMat, $env['term']);
        $enrollmentMat = $this->createEnrollment(['student_id' => $studentMat->id, 'school_class_id' => $env['class_mat']->id]);
        $selectionMat = $this->createSelection(['enrollment_id' => $enrollmentMat->id]);

        $this->actingAs($env['membro'])->from('/selections')
            ->delete('/selections/'.$selectionMat->id)
            ->assertForbidden();

        $this->actingAs($env['membro_mat'])->from('/selections')
            ->delete('/selections/'.$selectionMat->id)
            ->assertRedirect();
        $this->assertDatabaseMissing('selections', ['id' => $selectionMat->id]);
    }

    public function test_cen_selection_014_enrollments_lista_inscricoes_da_turma()
    {
        $env = $this->seedEnvironment();

        $plainStudent = $this->createStudent(['nompes' => 'Aluno Simples']);
        $this->createSchoolRecord($plainStudent, $env['term']);
        $plainEnrollment = $this->createEnrollment(['student_id' => $plainStudent->id, 'school_class_id' => $env['class']->id]);

        $recommendedStudent = $this->createStudent(['nompes' => 'Aluno Recomendado']);
        $this->createSchoolRecord($recommendedStudent, $env['term']);
        $recommendedEnrollment = $this->createEnrollment(['student_id' => $recommendedStudent->id, 'school_class_id' => $env['class']->id]);
        \App\Models\Recommendation::create(['student_id' => $recommendedStudent->id, 'requisition_id' => $env['requisition']->id]);

        $resp = $this->actingAs($env['secretaria'])->get('/selections/'.$env['class']->id.'/enrollments');

        $resp->assertOk();
        $inscricoes = $resp->viewData('inscricoes');
        $ids = $inscricoes->pluck('id')->all();
        $this->assertContains($env['enrollment']->id, $ids);
        $this->assertContains($plainEnrollment->id, $ids);
        $this->assertContains($recommendedEnrollment->id, $ids);

        // recommended students appear before plain students
        $posRecommended = array_search($recommendedEnrollment->id, $ids);
        $posPlain = array_search($plainEnrollment->id, $ids);
        $this->assertTrue($posRecommended < $posPlain, 'Recomendados devem aparecer antes');
    }

    public function test_cen_selection_015_select_unenrolled_cria_selecao_sem_inscricao_previa()
    {
        $env = $this->seedEnvironment();

        $student = $this->createStudent(['codpes' => 77777777]);
        $this->createSchoolRecord($student, $env['term']);

        $this->from('/selections')->actingAs($env['secretaria'])
            ->post('/selections/selectunenrolled', [
                'school_class_id' => $env['class']->id,
                'codpes' => 77777777,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('enrollments', [
            'student_id' => $student->id,
            'school_class_id' => $env['class']->id,
            'preferencia_horario' => 'Indiferente',
            'voluntario' => 0,
        ]);

        $selection = Selection::where('student_id', $student->id)->where('school_class_id', $env['class']->id)->first();
        $this->assertNotNull($selection);
        $this->assertSame('Ativo', $selection->sitatl);
        $this->assertSame($env['secretaria']->codpes, $selection->codpescad);

        $months = Frequency::where('student_id', $student->id)->where('school_class_id', $env['class']->id)
            ->pluck('month')->sort()->values()->all();
        $this->assertSame([3, 4, 5, 6], $months);
    }

    public function test_cen_selection_016_select_unenrolled_bloqueia_aluno_ja_inscrito_na_turma()
    {
        $env = $this->seedEnvironment();

        $this->from('/selections')->actingAs($env['secretaria'])
            ->post('/selections/selectunenrolled', [
                'school_class_id' => $env['class']->id,
                'codpes' => $env['student']->codpes,
            ])
            ->assertRedirect();

        $this->assertSessionHasWarningContaining('inscrito nesta turma');
        $this->assertSame(0, Selection::count());
    }

    public function test_cen_selection_017_select_unenrolled_bloqueia_aluno_ja_selecionado_em_outra_turma()
    {
        $env = $this->seedEnvironment();

        $studentX = $this->createStudent(['codpes' => 55555555]);
        $this->createSchoolRecord($studentX, $env['term']);

        $t2 = $this->createSchoolClass(['school_term_id' => $env['term']->id, 'coddis' => 'MAC0202']);
        $this->createRequisition(['instructor_id' => $env['instructor']->id, 'school_class_id' => $t2->id]);
        $enrollment2 = $this->createEnrollment(['student_id' => $studentX->id, 'school_class_id' => $t2->id]);
        $this->createSelection(['enrollment_id' => $enrollment2->id]);

        $this->from('/selections')->actingAs($env['secretaria'])
            ->post('/selections/selectunenrolled', [
                'school_class_id' => $env['class']->id,
                'codpes' => 55555555,
            ])
            ->assertRedirect();

        $this->assertSessionHasWarningContaining('já foi eleito');
    }

    public function test_cen_selection_018_select_unenrolled_bloqueia_aluno_sem_historico_escolar()
    {
        $env = $this->seedEnvironment();

        $student = $this->createStudent(['codpes' => 88888888]);

        $this->from('/selections')->actingAs($env['secretaria'])
            ->post('/selections/selectunenrolled', [
                'school_class_id' => $env['class']->id,
                'codpes' => 88888888,
            ])
            ->assertRedirect();

        $this->assertSessionHasWarningContaining('histórico');
        $this->assertSame(0, Selection::count());
    }
}