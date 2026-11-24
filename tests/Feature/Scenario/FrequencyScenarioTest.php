<?php

namespace Tests\Feature\Scenario;

use App\Models\Frequency;
use App\Models\Requisition;
use App\Models\SchoolClass;
use App\Models\Selection;
use App\Models\Student;
use Carbon\Carbon;
use Illuminate\Support\Facades\URL;

class FrequencyScenarioTest extends ScenarioTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow(Carbon::create(2026, 11, 23, 12, 0, 0, config('app.timezone')));
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_cen_frequency_001_index_lista_selecoes_ativo_do_docente_autenticado()
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
        $this->createSelection(['enrollment_id' => $otherEnrollment->id]);

        $resp = $this->actingAs($env['docente'])->get('/frequencies');

        $resp->assertOk();
        $selections = $resp->viewData('selections');
        $this->assertTrue($selections->contains('id', $selection->id));
        $this->assertFalse($selections->contains(fn ($s) => $s->student_id === $otherStudent->id));
    }

    public function test_cen_frequency_002_index_exige_papel_docente()
    {
        $this->createOpenTerm();
        $secretaria = $this->secretaria();

        $this->actingAs($secretaria)->get('/frequencies')->assertForbidden();
    }

    public function test_cen_frequency_003_show_via_url_assinada_valida()
    {
        $env = $this->seedEnvironment();
        $selection = $this->createSelection(['enrollment_id' => $env['enrollment']->id]);

        $url = URL::signedRoute('frequencies.show', [
            'schoolclass' => $env['class']->id,
            'tutor' => $env['student']->id,
        ]);

        $this->get($url)->assertOk();
    }

    public function test_cen_frequency_004_show_via_docente_autenticado_instrutor_da_turma()
    {
        $env = $this->seedEnvironment();
        $this->createSelection(['enrollment_id' => $env['enrollment']->id]);

        // instrutor da turma
        $this->actingAs($env['docente'])
            ->get('/frequencies/'.$env['class']->id.'/'.$env['student']->id)
            ->assertOk();

        // docente que não ministra a disciplina
        $outside = $this->docente();
        $this->from('/')->actingAs($outside)
            ->get('/frequencies/'.$env['class']->id.'/'.$env['student']->id)
            ->assertRedirect('/');
        $this->assertSessionHasWarningContaining('ministra');
    }

    public function test_cen_frequency_005_show_negado_para_tutor_que_nao_pertence_a_turma()
    {
        $env = $this->seedEnvironment();
        $this->createSelection(['enrollment_id' => $env['enrollment']->id]);

        $intruder = $this->createStudent(['nompes' => 'Intruso']);

        $this->from('/')->actingAs($env['docente'])
            ->get('/frequencies/'.$env['class']->id.'/'.$intruder->id)
            ->assertRedirect('/');
        $this->assertSessionHasWarningContaining('não pertence');
    }

    public function test_cen_frequency_006_update_alterna_registered_via_url_assinada()
    {
        $env = $this->seedEnvironment();
        $selection = $this->createSelection(['enrollment_id' => $env['enrollment']->id]);
        $frequency = $this->createFrequency([
            'selection_id' => $selection->id,
            'month' => now()->month,
            'registered' => false,
        ]);

        $confirmUrl = URL::signedRoute('frequencies.confirm', ['frequency' => $frequency->id]);
        $updateUrl = URL::signedRoute('frequencies.update', ['frequency' => $frequency->id]);

        $this->get($confirmUrl)->assertOk()->assertSee('Confirmar Frequência');
        $this->assertSame(0, (int) $frequency->fresh()->registered);

        $this->from('/')->patch($updateUrl)->assertRedirect('/');
        $this->assertSame(1, (int) $frequency->fresh()->registered);

        $this->from('/')->patch($updateUrl)->assertRedirect('/');
        $this->assertSame(0, (int) $frequency->fresh()->registered);
    }

    public function test_cen_frequency_007_update_bloqueado_quando_selecao_nao_ativa()
    {
        $env = $this->seedEnvironment();
        $selection = $this->createSelection(['enrollment_id' => $env['enrollment']->id, 'sitatl' => 'Concluido']);
        $frequency = $this->createFrequency([
            'selection_id' => $selection->id,
            'month' => min(12, now()->month + 1),
        ]);

        $confirmUrl = URL::signedRoute('frequencies.confirm', ['frequency' => $frequency->id]);
        $updateUrl = URL::signedRoute('frequencies.update', ['frequency' => $frequency->id]);

        $this->get($confirmUrl)->assertOk()->assertSee('Esta monitoria encontra-se com status Concluido.');
        $this->from('/frequencies')->patch($updateUrl)->assertRedirect('/frequencies');
        $this->assertSessionHasWarningContaining('status');
        $this->assertSame(0, (int) $frequency->fresh()->registered);
    }

    public function test_cen_frequency_008_update_bloqueado_para_mes_futuro()
    {
        $env = $this->seedEnvironment();
        $selection = $this->createSelection(['enrollment_id' => $env['enrollment']->id]);
        $frequency = $this->createFrequency([
            'selection_id' => $selection->id,
            'month' => min(12, now()->month + 1),
        ]);

        $confirmUrl = URL::signedRoute('frequencies.confirm', ['frequency' => $frequency->id]);
        $updateUrl = URL::signedRoute('frequencies.update', ['frequency' => $frequency->id]);

        $this->get($confirmUrl)->assertOk()->assertSee('liberada');
        $this->from('/frequencies')->patch($updateUrl)->assertRedirect('/frequencies');
        $this->assertSessionHasWarningContaining('liberada');
        $this->assertSame(0, (int) $frequency->fresh()->registered);
    }

    public function test_cen_frequency_009_update_bloqueado_antes_do_dia_20_do_mes()
    {
        Carbon::setTestNow(Carbon::create(2026, 11, 10, 12, 0, 0, config('app.timezone')));

        $env = $this->seedEnvironment();
        $selection = $this->createSelection(['enrollment_id' => $env['enrollment']->id]);
        $frequency = $this->createFrequency([
            'selection_id' => $selection->id,
            'month' => now()->month,
        ]);

        $confirmUrl = URL::signedRoute('frequencies.confirm', ['frequency' => $frequency->id]);
        $updateUrl = URL::signedRoute('frequencies.update', ['frequency' => $frequency->id]);

        $this->get($confirmUrl)->assertOk()->assertSee('liberada');
        $this->from('/frequencies')->patch($updateUrl)->assertRedirect('/frequencies');
        $this->assertSessionHasWarningContaining('liberada');
    }

    public function test_cen_frequency_010_update_liberado_a_partir_do_dia_20()
    {
        $env = $this->seedEnvironment();
        $selection = $this->createSelection(['enrollment_id' => $env['enrollment']->id]);
        $frequency = $this->createFrequency([
            'selection_id' => $selection->id,
            'month' => now()->month,
        ]);

        $confirmUrl = URL::signedRoute('frequencies.confirm', ['frequency' => $frequency->id]);
        $updateUrl = URL::signedRoute('frequencies.update', ['frequency' => $frequency->id]);

        $this->get($confirmUrl)->assertOk();
        $this->from('/')->patch($updateUrl)->assertRedirect('/');
        $this->assertSame(1, (int) $frequency->fresh()->registered);
    }

    public function test_cen_frequency_011_update_requer_autorizacao()
    {
        $env = $this->seedEnvironment();
        $selection = $this->createSelection(['enrollment_id' => $env['enrollment']->id]);
        $frequency = $this->createFrequency(['selection_id' => $selection->id]);

        // sem URL assinada e sem usuário => 403 na confirmação e na atualização
        $this->get('/frequencies/'.$frequency->id)->assertForbidden();
        $this->patch('/frequencies/'.$frequency->id)->assertForbidden();
    }

    public function test_cen_frequency_012_update_autenticado_como_instrutor_alterna_registered()
    {
        $env = $this->seedEnvironment();
        $selection = $this->createSelection(['enrollment_id' => $env['enrollment']->id]);
        $frequency = $this->createFrequency([
            'selection_id' => $selection->id,
            'month' => now()->month,
            'registered' => false,
        ]);

        $this->actingAs($env['docente'])
            ->get(route('frequencies.confirm', ['frequency' => $frequency->id]))
            ->assertOk();

        $this->actingAs($env['docente'])
            ->from('/frequencies')
            ->patch(route('frequencies.update', ['frequency' => $frequency->id]))
            ->assertRedirect('/frequencies');

        $this->assertSame(1, (int) $frequency->fresh()->registered);
    }

    public function test_cen_frequency_013_update_autenticado_sem_ser_instrutor_recebe_403()
    {
        $env = $this->seedEnvironment();
        $selection = $this->createSelection(['enrollment_id' => $env['enrollment']->id]);
        $frequency = $this->createFrequency([
            'selection_id' => $selection->id,
            'month' => now()->month,
        ]);

        $this->actingAs($this->docente())
            ->get(route('frequencies.confirm', ['frequency' => $frequency->id]))
            ->assertForbidden();

        $this->actingAs($this->docente())
            ->patch(route('frequencies.update', ['frequency' => $frequency->id]))
            ->assertForbidden();

        $this->assertSame(0, (int) $frequency->fresh()->registered);
    }
}