<?php

namespace Tests\Feature\Scenario;

use App\Models\Selection;
use App\Models\Student;
use App\Mail\NotifyCertificateRequest;
use Illuminate\Support\Facades\Mail;
use Mockery;

class CertificateScenarioTest extends ScenarioTestCase
{
    protected function envWithSelections(): array
    {
        $env = $this->seedEnvironment();
        $activeSelection = $this->createSelection(['enrollment_id' => $env['enrollment']->id]);
        $concludedSelection = $this->createSelection(['enrollment_id' => $env['enrollment']->id, 'sitatl' => 'Concluido']);
        $desligadoSelection = $this->createSelection(['enrollment_id' => $env['enrollment']->id, 'sitatl' => 'Desligado']);

        return compact('env', 'activeSelection', 'concludedSelection', 'desligadoSelection');
    }

    public function test_cen_certificate_001_index_secretaria_admin_lista_todas_as_selecoes_nao_desligado()
    {
        $fixture = $this->envWithSelections();
        $env = $fixture['env'];

        foreach (['secretaria', 'admin'] as $role) {
            $resp = $this->actingAs($env[$role])->get('/certificates');
            $resp->assertOk();
            $ids = $resp->viewData('selections')->pluck('id')->all();
            $this->assertContains($fixture['activeSelection']->id, $ids);
            $this->assertContains($fixture['concludedSelection']->id, $ids);
            $this->assertNotContains($fixture['desligadoSelection']->id, $ids);
        }
    }

    public function test_cen_certificate_002_index_aluno_lista_apenas_as_proprias_selecoes()
    {
        $env = $this->seedEnvironment();

        $mySelection = $this->createSelection(['enrollment_id' => $env['enrollment']->id]);

        $otherStudent = $this->createStudent(['nompes' => 'Outro Aluno']);
        $otherClass = $this->createSchoolClass(['school_term_id' => $env['term']->id, 'coddis' => 'MAC0202']);
        $this->createRequisition(['instructor_id' => $env['instructor']->id, 'school_class_id' => $otherClass->id]);
        $otherEnrollment = $this->createEnrollment(['student_id' => $otherStudent->id, 'school_class_id' => $otherClass->id]);
        $this->createSelection(['enrollment_id' => $otherEnrollment->id]);

        $resp = $this->actingAs($env['aluno'])->get('/certificates');
        $resp->assertOk();
        $ids = $resp->viewData('selections')->pluck('id')->all();
        $this->assertContains($mySelection->id, $ids);
        $this->assertNotContains(Selection::where('student_id', $otherStudent->id)->first()->id, $ids);
    }

    public function test_cen_certificate_003_index_exige_papel_aluno_ou_historico_de_selecao()
    {
        $this->createOpenTerm();
        $semSelecao = $this->createUser('Monitor');

        $this->actingAs($semSelecao)->get('/certificates')->assertForbidden();
    }

    public function test_cen_certificate_004_index_vazio_exibe_aviso_de_nenhuma_monitoria()
    {
        $this->createOpenTerm();
        $alunoSemMonitoria = $this->aluno();

        $this->from('/')->actingAs($alunoSemMonitoria)->get('/certificates')->assertRedirect('/');
        $this->assertSessionHasWarningContaining('nenhuma monitoria');
    }

    public function test_cen_certificate_005_make_verifica_propriedade_da_selecao()
    {
        $fixture = $this->envWithSelections();

        $otherStudent = $this->createStudent(['nompes' => 'Outro Aluno']);
        $otherClass = $this->createSchoolClass(['school_term_id' => $fixture['env']['term']->id, 'coddis' => 'MAC0202']);
        $this->createRequisition(['instructor_id' => $fixture['env']['instructor']->id, 'school_class_id' => $otherClass->id]);
        $otherEnrollment = $this->createEnrollment(['student_id' => $otherStudent->id, 'school_class_id' => $otherClass->id]);
        $otherSelection = $this->createSelection(['enrollment_id' => $otherEnrollment->id]);

        // aluno não relacionado tenta emitir atestado de outrem
        $this->actingAs($fixture['env']['aluno'])->get('/certificates/make/'.$otherSelection->id)->assertForbidden();

        // Secretaria pode emitir
        $this->mockLaraTeX();
        $this->actingAs($fixture['env']['secretaria'])->get('/certificates/make/'.$otherSelection->id)->assertOk();
    }

    public function test_cen_certificate_006_make_para_selecao_concluido_renderiza_atestado()
    {
        $fixture = $this->envWithSelections();
        $concluded = $fixture['concludedSelection'];

        $this->mockLaraTeX();

        $resp = $this->actingAs($fixture['env']['secretaria'])->get('/certificates/make/'.$concluded->id);
        $resp->assertOk();
    }

    public function test_cen_certificate_007_make_para_selecao_ativo_renderiza_atestado()
    {
        $fixture = $this->envWithSelections();
        $active = $fixture['activeSelection'];

        $this->mockLaraTeX();

        $resp = $this->actingAs($fixture['env']['secretaria'])->get('/certificates/make/'.$active->id);
        $resp->assertOk();
    }

    public function test_cen_certificate_008_make_aluno_nao_baixa_atestado_e_notifica_secretaria()
    {
        $fixture = $this->envWithSelections();
        $concluded = $fixture['concludedSelection'];

        config(['certificate.secretaria_email' => 'secretaria@ime.usp.br']);
        Mail::fake();

        $resp = $this->from('/certificates')->actingAs($fixture['env']['aluno'])->get('/certificates/make/'.$concluded->id);

        // o aluno não baixa mais o certificado com assinatura em foto
        $resp->assertRedirect('/certificates');
        $this->assertEquals('Sua solicitação de Certificado de Monitoria foi registrada. A Secretaria de Monitoria será notificada e o certificado, após validação no USP ASSINA, será enviado a você por e-mail.', $this->app['session']->get('alert-info'));

        // a Secretaria é notificada da solicitação
        Mail::assertQueued(NotifyCertificateRequest::class, 1);
    }

    public function test_cen_certificate_009_make_aluno_sem_email_de_secretaria_configurado_nao_envia()
    {
        $fixture = $this->envWithSelections();
        $concluded = $fixture['concludedSelection'];

        config(['certificate.secretaria_email' => '']);
        Mail::fake();

        $resp = $this->from('/certificates')->actingAs($fixture['env']['aluno'])->get('/certificates/make/'.$concluded->id);

        $resp->assertRedirect('/certificates');
        Mail::assertNothingQueued();
    }

    protected function mockLaraTeX(): void
    {
        $laratex = Mockery::mock('overload:Ismaelw\LaraTeX\LaraTeX');
        $laratex->shouldReceive('with')->andReturnSelf();
        $laratex->shouldReceive('download')->andReturn(response('atestado-pdf-fake'));
    }
}