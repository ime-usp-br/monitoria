<?php

namespace Tests\Feature\Scenario;

use App\Models\Enrollment;
use App\Models\Frequency;

class TutorScenarioTest extends ScenarioTestCase
{
    protected function envWithSelection(): array
    {
        $env = $this->seedEnvironment();
        $selection = $this->createSelection(['enrollment_id' => $env['enrollment']->id]);

        // selection of another instructor
        $otherInstructor = $this->createInstructor();
        $otherClass = $this->createSchoolClass([
            'school_term_id' => $env['term']->id,
            'coddis' => 'MAC0202',
            'instructors' => [$otherInstructor->id],
        ]);
        $otherReq = $this->createRequisition(['instructor_id' => $otherInstructor->id, 'school_class_id' => $otherClass->id]);
        $otherStudent = $this->createStudent(['nompes' => 'Outro Monitor']);
        $otherEnrollment = $this->createEnrollment(['student_id' => $otherStudent->id, 'school_class_id' => $otherClass->id]);
        $otherSelection = $this->createSelection(['enrollment_id' => $otherEnrollment->id, 'sitatl' => 'Concluido']);

        return compact('env', 'selection', 'otherSelection', 'otherInstructor');
    }

    public function test_cen_tutor_001_index_por_papel_admin_secretaria_presidente_veem_todas_as_selecoes()
    {
        $fixture = $this->envWithSelection();

        foreach (['secretaria', 'admin', 'presidente'] as $role) {
            $resp = $this->actingAs($fixture['env'][$role])->get('/tutors');
            $resp->assertOk();
            $this->assertCount(2, $resp->viewData('selections'));
        }
    }

    public function test_cen_tutor_002_index_membro_comissao_ve_apenas_o_departamento()
    {
        $fixture = $this->envWithSelection();

        $resp = $this->actingAs($fixture['env']['membro'])->get('/tutors');
        $resp->assertOk();
        $this->assertSame(1, $resp->viewData('selections')->count());
    }

    public function test_cen_tutor_003_index_docente_ve_selecoes_de_suas_solicitacoes()
    {
        $fixture = $this->envWithSelection();

        $resp = $this->actingAs($fixture['env']['docente'])->get('/tutors');
        $resp->assertOk();
        $ids = $resp->viewData('selections')->pluck('id')->all();
        $this->assertContains($fixture['selection']->id, $ids);
        $this->assertNotContains($fixture['otherSelection']->id, $ids);
    }

    public function test_cen_tutor_004_index_com_papel_sem_acesso_retorna_403()
    {
        $fixture = $this->envWithSelection();

        $this->actingAs($fixture['env']['aluno'])->get('/tutors')->assertForbidden();
    }

    public function test_cen_tutor_005_revoke_exige_secretaria_admin()
    {
        $fixture = $this->envWithSelection();

        $this->from('/tutors')->actingAs($fixture['env']['docente'])
            ->patch('/tutors/revoke/'.$fixture['selection']->id, ['motdes' => 'x'])
            ->assertForbidden();
    }

    public function test_cen_tutor_006_revoke_bloqueia_selecao_nao_ativa()
    {
        $fixture = $this->envWithSelection();

        $this->from('/tutors')->actingAs($fixture['env']['secretaria'])
            ->patch('/tutors/revoke/'.$fixture['otherSelection']->id, ['motdes' => 'motivo'])
            ->assertRedirect();
        $this->assertSessionHasWarningContaining('status');
        $this->assertSame('Concluido', $fixture['otherSelection']->fresh()->sitatl);
    }

    public function test_cen_tutor_007_revoke_exclui_frequencias_futuras_nao_registradas()
    {
        $fixture = $this->envWithSelection();
        $selection = $fixture['selection'];
        $currentMonth = (int) date('m');

        Frequency::create(['student_id' => $selection->student_id, 'school_class_id' => $selection->school_class_id, 'month' => $currentMonth, 'registered' => false]);
        Frequency::create(['student_id' => $selection->student_id, 'school_class_id' => $selection->school_class_id, 'month' => min(12, $currentMonth + 1), 'registered' => false]);
        $past = Frequency::create(['student_id' => $selection->student_id, 'school_class_id' => $selection->school_class_id, 'month' => max(1, $currentMonth - 1), 'registered' => true]);

        $this->from('/tutors')->actingAs($fixture['env']['secretaria'])
            ->patch('/tutors/revoke/'.$selection->id, ['motdes' => 'motivo'])
            ->assertRedirect();

        $remaining = Frequency::where('student_id', $selection->student_id)
            ->where('school_class_id', $selection->school_class_id)
            ->pluck('id')->all();
        $this->assertContains($past->id, $remaining);
        $this->assertCount(1, $remaining);
    }

    public function test_cen_tutor_008_revoke_define_desligado_com_motivo_e_data_de_fim()
    {
        $fixture = $this->envWithSelection();
        $selection = $fixture['selection'];

        $this->from('/tutors')->actingAs($fixture['env']['secretaria'])
            ->patch('/tutors/revoke/'.$selection->id, ['motdes' => 'não compareceu'])
            ->assertRedirect();

        $selection->refresh();
        $this->assertSame('Desligado', $selection->sitatl);
        $this->assertSame('não compareceu', $selection->motdes);
        $this->assertSame(date('d/m/Y'), $selection->dtafimvin);
    }

    public function test_cen_tutor_009_turn_into_volunteer_exige_secretaria_admin_e_selecao_ativa()
    {
        $fixture = $this->envWithSelection();
        $selection = $fixture['selection'];
        $enrollment = Enrollment::find($selection->enrollment_id);
        $enrollment->update(['voluntario' => 0]);

        $this->from('/tutors')->actingAs($fixture['env']['secretaria'])
            ->patch('/tutors/turnintovolunteer/'.$selection->id)
            ->assertRedirect();

        $this->assertSame(1, (int) Enrollment::find($selection->enrollment_id)->fresh()->voluntario);

        // seleção nao ativa -> bloqueado
        $this->from('/tutors')->actingAs($fixture['env']['secretaria'])
            ->patch('/tutors/turnintovolunteer/'.$fixture['otherSelection']->id)
            ->assertRedirect();
        $this->assertSessionHasWarningContaining('status');
    }

    public function test_cen_tutor_010_turn_into_non_volunteer_reverte_o_flag()
    {
        $fixture = $this->envWithSelection();
        $selection = $fixture['selection'];
        $enrollment = Enrollment::find($selection->enrollment_id);
        $enrollment->update(['voluntario' => 1]);

        $this->from('/tutors')->actingAs($fixture['env']['secretaria'])
            ->patch('/tutors/turnintononvolunteer/'.$selection->id)
            ->assertRedirect();

        $this->assertSame(0, (int) Enrollment::find($selection->enrollment_id)->fresh()->voluntario);
    }

    public function test_cen_tutor_011_alternancia_nao_altera_outras_inscricoes()
    {
        $fixture = $this->envWithSelection();
        $selection = $fixture['selection'];

        $studentId = $selection->student_id;
        $otherClass = $fixture['otherSelection']->schoolclass;
        $otherEnrollment = Enrollment::create([
            'student_id' => $studentId,
            'school_class_id' => $otherClass->id,
            'voluntario' => 0,
            'disponibilidade_diurno' => 0,
            'disponibilidade_noturno' => 0,
            'preferencia_horario' => 'Noite',
        ]);

        $this->from('/tutors')->actingAs($fixture['env']['secretaria'])
            ->patch('/tutors/turnintovolunteer/'.$selection->id)
            ->assertRedirect();

        $this->assertSame(1, (int) Enrollment::find($selection->enrollment_id)->fresh()->voluntario);
        $this->assertSame(0, (int) Enrollment::find($otherEnrollment->id)->fresh()->voluntario);
    }
}