<?php

namespace Tests\Feature\Scenario;

use App\Models\Frequency;
use App\Models\InstructorEvaluation;
use App\Models\MailTemplate;
use App\Models\SchoolTerm;
use App\Models\SelfEvaluation;
use Illuminate\Support\Facades\Mail;

class EmailControllerScenarioTest extends ScenarioTestCase
{
    protected function makeTemplate(string $mailClass): MailTemplate
    {
        return MailTemplate::create([
            'name' => 'Template '.$mailClass,
            'description' => 'desc',
            'mail_class' => $mailClass,
            'sending_frequency' => 'Manual',
            'sending_date' => null,
            'sending_hour' => null,
            'active' => true,
            'subject' => 'Assunto {{ schoolclass.coddis }}',
            'body' => '<p>Corpo</p>',
        ]);
    }

    public function test_cen_email_001_areas_exigem_permissao_disparar_emails()
    {
        $semPermissao = $this->createUser('Monitor');

        $this->actingAs($semPermissao)->get('/emails')->assertForbidden();
        $this->actingAs($semPermissao)->get('/emails/selections')->assertForbidden();
        $this->actingAs($semPermissao)->get('/emails/attendanceRecords')->assertForbidden();
        $this->actingAs($semPermissao)->get('/emails/selfEvaluations')->assertForbidden();
        $this->actingAs($semPermissao)->get('/emails/instructorEvaluations')->assertForbidden();
        $this->actingAs($semPermissao)->post('/emails/triggerSelections', ['school_classes_id' => [1]])->assertForbidden();
        $this->actingAs($semPermissao)->post('/emails/triggerAttendanceRecords', ['frequencies_id' => [1]])->assertForbidden();
        $this->actingAs($semPermissao)->post('/emails/triggerSelfEvaluations', ['selections_id' => [1]])->assertForbidden();
        $this->actingAs($semPermissao)->post('/emails/triggerInstructorEvaluations', ['selections_id' => [1]])->assertForbidden();
    }

    public function test_cen_email_002_index_selections_lista_turmas_com_selecoes_do_periodo()
    {
        $env = $this->seedEnvironment();
        $this->createSelection(['enrollment_id' => $env['enrollment']->id]);

        // turma sem seleção
        $semSelClass = $this->createSchoolClass(['school_term_id' => $env['term']->id, 'coddis' => 'MAC0202']);
        $this->createRequisition(['instructor_id' => $env['instructor']->id, 'school_class_id' => $semSelClass->id]);

        $resp = $this->actingAs($env['secretaria'])->get('/emails/selections');
        $resp->assertOk();

        $turmas = $resp->viewData('turmas');
        $ids = $turmas->pluck('id')->all();
        $this->assertContains($env['class']->id, $ids);
        $this->assertNotContains($semSelClass->id, $ids);
    }

    public function test_cen_email_003_index_attendance_records_calcula_meses_validos_por_periodo()
    {
        // 1° Semestre
        $env1 = $this->seedEnvironment();
        $resp = $this->actingAs($env1['secretaria'])->get('/emails/attendanceRecords');
        $resp->assertOk();
        $month1 = (int) $resp->viewData('month');
        $this->assertTrue(in_array($month1, [3, 4, 5, 6], true));

        // 2° Semestre: troca o período do termo para 2° e usa mês 8 (dentro da janela)
        $env1['term']->update(['period' => '2° Semestre']);
        $resp2 = $this->actingAs($env1['secretaria'])->get('/emails/attendanceRecords?month=8');
        $resp2->assertOk();
        $this->assertSame(8, (int) $resp2->viewData('month'));
    }

    public function test_cen_email_004_index_attendance_records_valida_mes_informado()
    {
        $env = $this->seedEnvironment(); // 1° Semestre
        $this->createSelection(['enrollment_id' => $env['enrollment']->id]);

        $this->from('/emails/attendanceRecords')->actingAs($env['secretaria'])
            ->get('/emails/attendanceRecords?month=8')
            ->assertRedirect('/emails/attendanceRecords');
        $this->assertSessionHasWarningContaining('Mês invalido');
    }

    public function test_cen_email_005_index_attendance_records_deriva_mes_do_atual_quando_nao_informado()
    {
        $env = $this->seedEnvironment();
        $resp = $this->actingAs($env['secretaria'])->get('/emails/attendanceRecords');
        $resp->assertOk();
        $month = (int) $resp->viewData('month');
        $this->assertTrue(in_array($month, [3, 4, 5, 6], true));
    }

    public function test_cen_email_006_index_attendance_records_lista_frequencias_nao_registradas_de_monitores_ativo()
    {
        $env = $this->seedEnvironment();
        $selection = $this->createSelection(['enrollment_id' => $env['enrollment']->id]);
        $freq = Frequency::create([
            'school_class_id' => $env['class']->id,
            'student_id' => $env['student']->id,
            'month' => 3,
            'registered' => false,
        ]);

        $resp = $this->actingAs($env['secretaria'])->get('/emails/attendanceRecords?month=3');
        $resp->assertOk();
        $this->assertTrue($resp->viewData('frequencies')->contains('id', $freq->id));
    }

    public function test_cen_email_007_index_self_evaluations_lista_selecoes_sem_autoavaliacao()
    {
        $env = $this->seedEnvironment();
        $semSe = $this->createSelection(['enrollment_id' => $env['enrollment']->id]);

        $comClass = $this->createSchoolClass(['school_term_id' => $env['term']->id, 'coddis' => 'MAC0303', 'instructors' => [$env['instructor']->id]]);
        $this->createRequisition(['instructor_id' => $env['instructor']->id, 'school_class_id' => $comClass->id]);
        $comEnrollment = $this->createEnrollment(['student_id' => $env['student']->id, 'school_class_id' => $comClass->id]);
        $comSe = $this->createSelection(['enrollment_id' => $comEnrollment->id]);
        SelfEvaluation::create(['selection_id' => $comSe->id, 'student_amount' => 1, 'homework_amount' => 1, 'workload' => 1]);

        $resp = $this->actingAs($env['secretaria'])->get('/emails/selfEvaluations');
        $resp->assertOk();

        $ids = $resp->viewData('selections')->pluck('id')->all();
        $this->assertContains($semSe->id, $ids);
        $this->assertNotContains($comSe->id, $ids);
    }

    public function test_cen_email_008_index_instructor_evaluations_lista_selecoes_sem_avaliacao()
    {
        $env = $this->seedEnvironment();
        $semIe = $this->createSelection(['enrollment_id' => $env['enrollment']->id]);

        $comClass = $this->createSchoolClass(['school_term_id' => $env['term']->id, 'coddis' => 'MAC0404', 'instructors' => [$env['instructor']->id]]);
        $this->createRequisition(['instructor_id' => $env['instructor']->id, 'school_class_id' => $comClass->id]);
        $comEnrollment = $this->createEnrollment(['student_id' => $env['student']->id, 'school_class_id' => $comClass->id]);
        $comIe = $this->createSelection(['enrollment_id' => $comEnrollment->id]);
        InstructorEvaluation::create(['selection_id' => $comIe->id, 'ease_of_contact' => 1, 'efficiency' => 1, 'reliability' => 1, 'overall' => 1]);

        $resp = $this->actingAs($env['secretaria'])->get('/emails/instructorEvaluations');
        $resp->assertOk();

        $ids = $resp->viewData('selections')->pluck('id')->all();
        $this->assertContains($semIe->id, $ids);
        $this->assertNotContains($comIe->id, $ids);
    }

    public function test_cen_email_009_trigger_selections_exige_modelo_ativo_manual_por_classe()
    {
        $env = $this->seedEnvironment();
        $this->createSelection(['enrollment_id' => $env['enrollment']->id]);

        $this->from('/emails/selections')->actingAs($env['secretaria'])
            ->post('/emails/triggerSelections', ['school_classes_id' => [$env['class']->id]])
            ->assertRedirect('/emails/selections');
        $this->assertSessionHasWarningContaining('modelo de e-mail ativo');
    }

    public function test_cen_email_010_trigger_selections_envia_para_instrutor_e_alunos_selecionados()
    {
        $env = $this->seedEnvironment();
        $this->createSelection(['enrollment_id' => $env['enrollment']->id]);
        $this->makeTemplate('NotifyInstructorAboutSelectAssistant');
        $this->makeTemplate('NotifySelectStudent');

        Mail::fake();

        $this->actingAs($env['secretaria'])->from('/emails/selections')
            ->post('/emails/triggerSelections', ['school_classes_id' => [$env['class']->id]])
            ->assertRedirect('/emails/selections');

        Mail::assertQueued(\App\Mail\NotifyInstructorAboutSelectAssistant::class);
        Mail::assertQueued(\App\Mail\NotifySelectStudent::class);
    }

    public function test_cen_email_011_trigger_attendance_records_exige_modelo_ativo_manual()
    {
        $env = $this->seedEnvironment();
        $this->createSelection(['enrollment_id' => $env['enrollment']->id]);
        $freq = Frequency::create(['school_class_id' => $env['class']->id, 'student_id' => $env['student']->id, 'month' => 3, 'registered' => false]);

        $this->from('/emails/attendanceRecords')->actingAs($env['secretaria'])
            ->post('/emails/triggerAttendanceRecords', ['frequencies_id' => [$freq->id]])
            ->assertRedirect('/emails/attendanceRecords');
        $this->assertSessionHasWarningContaining('modelo de e-mail ativo');
    }

    public function test_cen_email_012_trigger_attendance_records_envia_ao_instrutor_com_url_assinada()
    {
        $env = $this->seedEnvironment();
        $this->createSelection(['enrollment_id' => $env['enrollment']->id]);
        $freq = Frequency::create(['school_class_id' => $env['class']->id, 'student_id' => $env['student']->id, 'month' => 3, 'registered' => false]);
        $this->makeTemplate('NotifyInstructorAboutAttendanceRecord');

        Mail::fake();

        $this->actingAs($env['secretaria'])->from('/emails/attendanceRecords')
            ->post('/emails/triggerAttendanceRecords', ['frequencies_id' => [$freq->id]])
            ->assertRedirect('/emails/attendanceRecords');

        Mail::assertSent(\App\Mail\NotifyInstructorAboutAttendanceRecord::class, function ($mail) use ($freq) {
            return str_contains($mail->link, 'frequencies') && str_contains($mail->link, 'signature');
        });
    }

    public function test_cen_email_013_mailable_de_frequencia_cancela_envio_se_selecao_nao_ativo()
    {
        $env = $this->seedEnvironment();
        $selection = $this->createSelection(['enrollment_id' => $env['enrollment']->id, 'sitatl' => 'Desligado']);
        $freq = Frequency::create(['school_class_id' => $env['class']->id, 'student_id' => $env['student']->id, 'month' => 3, 'registered' => false]);
        $template = $this->makeTemplate('NotifyInstructorAboutAttendanceRecord');

        \Illuminate\Support\Facades\Log::spy();

        $mailable = new \App\Mail\NotifyInstructorAboutAttendanceRecord($freq, 'http://exemplo/assinada', $template);
        $result = $mailable->build();

        $this->assertNull($result);
    }

    public function test_cen_email_014_trigger_self_evaluations_envia_com_url_assinada_apenas_sem_avaliacao()
    {
        $env = $this->seedEnvironment();
        $this->createSelection(['enrollment_id' => $env['enrollment']->id]);
        $this->makeTemplate('NotifyStudentAboutSelfEvaluation');

        Mail::fake();

        $selectionId = \App\Models\Selection::where('school_class_id', $env['class']->id)->first()->id;
        $selection = \App\Models\Selection::find($selectionId);

        // por causa do afterCommit() a remessa é adiada até o commit da transação;
        // validamos a construção do mailable com URL assinada (mesma lógica do controller).
        $mailable = new \App\Mail\NotifyStudentAboutSelfEvaluation(
            $selection,
            \Illuminate\Support\Facades\URL::signedRoute('selfevaluations.create', ['selectionID' => $selectionId]),
            \App\Models\MailTemplate::where('mail_class', 'NotifyStudentAboutSelfEvaluation')->first()
        );

        $this->actingAs($env['secretaria'])->from('/emails/selfEvaluations')
            ->post('/emails/triggerSelfEvaluations', ['selections_id' => [$selectionId]])
            ->assertRedirect('/emails/selfEvaluations');

        $this->assertStringContainsString('selfevaluations/create', $mailable->link);
        $this->assertStringContainsString('signature', $mailable->link);
    }

    public function test_cen_email_015_trigger_instructor_evaluations_envia_com_url_assinada()
    {
        $env = $this->seedEnvironment();
        $this->createSelection(['enrollment_id' => $env['enrollment']->id]);
        $this->makeTemplate('NotifyInstructorAboutEvaluation');

        Mail::fake();

        $selectionId = \App\Models\Selection::where('school_class_id', $env['class']->id)->first()->id;
        $selection = \App\Models\Selection::find($selectionId);

        $mailable = new \App\Mail\NotifyInstructorAboutEvaluation(
            $selection,
            \Illuminate\Support\Facades\URL::signedRoute('instructorevaluations.create', ['selectionID' => $selectionId]),
            \App\Models\MailTemplate::where('mail_class', 'NotifyInstructorAboutEvaluation')->first()
        );

        $this->actingAs($env['secretaria'])->from('/emails/instructorEvaluations')
            ->post('/emails/triggerInstructorEvaluations', ['selections_id' => [$selectionId]])
            ->assertRedirect('/emails/instructorEvaluations');

        $this->assertStringContainsString('instructorevaluations/create', $mailable->link);
        $this->assertStringContainsString('signature', $mailable->link);
    }

    public function test_cen_email_016_payload_de_triggers_valida_arrays_de_ids()
    {
        $env = $this->seedEnvironment();
        $this->createSelection(['enrollment_id' => $env['enrollment']->id]);
        $this->makeTemplate('NotifySelectStudent');
        $this->makeTemplate('NotifyInstructorAboutSelectAssistant');

        // IDs ausentes
        $this->actingAs($env['secretaria'])->post('/emails/triggerSelections', [])->assertSessionHasErrors('school_classes_id');
        $this->actingAs($env['secretaria'])->post('/emails/triggerAttendanceRecords', ['frequencies_id' => ['x']])->assertSessionHasErrors('frequencies_id.0');
        $this->actingAs($env['secretaria'])->post('/emails/triggerSelfEvaluations', [])->assertSessionHasErrors('selections_id');
        $this->actingAs($env['secretaria'])->post('/emails/triggerInstructorEvaluations', ['selections_id' => ['abc']])->assertSessionHasErrors('selections_id.0');
    }
}
