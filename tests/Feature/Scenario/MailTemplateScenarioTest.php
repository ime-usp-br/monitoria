<?php

namespace Tests\Feature\Scenario;

use App\Models\MailTemplate;
use App\Models\Selection;
use App\Models\Frequency;
use Illuminate\Support\Facades\Mail;

class MailTemplateScenarioTest extends ScenarioTestCase
{
    protected function createMailTemplate(array $attributes = []): MailTemplate
    {
        return MailTemplate::create(array_merge([
            'name' => 'Template '.mt_rand(1000, 9999),
            'description' => 'Descrição do template',
            'mail_class' => 'NotifySelectStudent',
            'sending_frequency' => 'Manual',
            'sending_date' => null,
            'sending_hour' => null,
            'active' => false,
            'subject' => 'Assunto do email',
            'body' => '<p>Corpo do email</p>',
        ], $attributes));
    }

    private function payload(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Template Payload',
            'description_and_mail_class' => json_encode([
                'description' => 'Descricao de teste',
                'mail_class' => 'NotifySelectStudent',
            ]),
            'subject' => 'Assunto',
            'body' => 'Corpo',
            'sending_frequency' => 'Manual',
            'sending_date' => null,
            'sending_hour' => null,
        ], $overrides);
    }

    public function test_cen_mailtemplate_001_crud_exige_permissao_editar_emails()
    {
        $semPermissao = $this->createUser('Monitor');
        $template = $this->createMailTemplate();

        $this->actingAs($semPermissao)->get('/mailtemplates')->assertForbidden();
        $this->actingAs($semPermissao)->get('/mailtemplates/create')->assertForbidden();
        $this->actingAs($semPermissao)->post('/mailtemplates', $this->payload())->assertForbidden();
        $this->actingAs($semPermissao)->get('/mailtemplates/'.$template->id.'/edit')->assertForbidden();
        $this->actingAs($semPermissao)->put('/mailtemplates/'.$template->id, $this->payload())->assertForbidden();
        $this->actingAs($semPermissao)->delete('/mailtemplates/'.$template->id)->assertForbidden();
    }

    public function test_cen_mailtemplate_001b_secretaria_nao_pode_editar_templates()
    {
        $secretaria = $this->secretaria();
        $template = $this->createMailTemplate();

        $this->actingAs($secretaria)->get('/mailtemplates')->assertForbidden();
        $this->actingAs($secretaria)->post('/mailtemplates', $this->payload())->assertForbidden();
        $this->actingAs($secretaria)->put('/mailtemplates/'.$template->id, $this->payload())->assertForbidden();
        $this->actingAs($secretaria)->delete('/mailtemplates/'.$template->id)->assertForbidden();
    }

    public function test_cen_mailtemplate_001c_template_rejeita_diretivas_php()
    {
        $admin = $this->admin();

        $this->actingAs($admin)->post('/mailtemplates', $this->payload([
            'body' => '@php system("id"); @endphp',
        ]))->assertSessionHasErrors('body');

        $this->actingAs($admin)->post('/mailtemplates', $this->payload([
            'subject' => '<?php echo "x"; ?>',
        ]))->assertSessionHasErrors('subject');

        $this->actingAs($admin)->post('/mailtemplates', $this->payload([
            'body' => '<p>Olá {{ $student->nompes }}</p>',
        ]))->assertSessionHasNoErrors();
    }

    public function test_cen_mailtemplate_001d_renderiza_subject_e_body_reais()
    {
        $env = $this->seedEnvironment();
        $selection = $this->createSelection(['enrollment_id' => $env['enrollment']->id]);

        $template = $this->createMailTemplate([
            'subject' => 'Turma {{ $schoolclass->coddis }}',
            'body' => '<p>Olá {{ $student->nompes }}</p>',
        ]);

        $mailable = new \App\Mail\NotifySelectStudent($selection->student, $selection->schoolclass, $template);
        $html = $mailable->render();

        $this->assertSame('Turma '.$env['class']->coddis, $mailable->subject);
        $this->assertStringContainsString($selection->student->nompes, $html);
        $this->assertStringContainsString('Olá', $html);
    }

    public function test_cen_mailtemplate_002_store_decodifica_campo_combinado()
    {
        $admin = $this->admin();

        $this->actingAs($admin)->post('/mailtemplates', $this->payload());

        $this->assertDatabaseHas('mail_templates', ['name' => 'Template Payload', 'description' => 'Descricao de teste', 'mail_class' => 'NotifySelectStudent']);
    }

    public function test_cen_mailtemplate_003_store_rejeita_nome_duplicado()
    {
        $admin = $this->admin();
        $this->createMailTemplate(['name' => 'Template A']);

        $this->from('/mailtemplates')->actingAs($admin)
            ->post('/mailtemplates', array_merge($this->payload(), ['name' => 'Template A']))
            ->assertRedirect('/mailtemplates');

        $this->assertSessionHasWarningContaining('modelo com esse nome');
        $this->assertSame(1, MailTemplate::where('name', 'Template A')->count());
    }

    public function test_cen_mailtemplate_004_validacao_de_campos_do_template()
    {
        $admin = $this->admin();

        $cases = [
            'name_ausente' => ['name' => null],
            'subject_excede_256' => ['subject' => str_repeat('a', 257)],
            'body_excede_8192' => ['body' => str_repeat('a', 8193)],
            'frequencia_nao_manual_sem_data' => ['sending_frequency' => 'Única', 'sending_date' => null, 'sending_hour' => '08:00'],
            'frequencia_nao_manual_sem_hora' => ['sending_frequency' => 'Mensal', 'sending_date' => '1', 'sending_hour' => null],
        ];

        foreach ($cases as $key => $overrides) {
            $this->actingAs($admin)->post('/mailtemplates', array_merge($this->payload(), $overrides))->assertSessionHasErrors();
        }
    }

    public function test_cen_mailtemplate_005_update_rejeita_nome_duplicado_excluindo_a_si_mesmo()
    {
        $admin = $this->admin();
        $a = $this->createMailTemplate(['name' => 'Template A']);
        $this->createMailTemplate(['name' => 'Template B']);

        // atualizar A para B deve falhar
        $this->from('/mailtemplates')->actingAs($admin)
            ->put('/mailtemplates/'.$a->id, array_merge($this->payload(), ['name' => 'Template B']))
            ->assertRedirect('/mailtemplates');
        $this->assertSessionHasWarningContaining('modelo com esse nome');

        // manter o próprio nome passa
        $this->from('/mailtemplates')->actingAs($admin)
            ->put('/mailtemplates/'.$a->id, array_merge($this->payload(), ['name' => 'Template A']))
            ->assertRedirect('/mailtemplates');
        $this->assertDatabaseHas('mail_templates', ['id' => $a->id, 'name' => 'Template A']);
    }

    public function test_cen_mailtemplate_006_update_rejeita_ativar_outro_manual_ativo_da_mesma_classe()
    {
        $admin = $this->admin();
        $t1 = $this->createMailTemplate(['name' => 'T1', 'mail_class' => 'NotifySelectStudent', 'active' => true, 'sending_frequency' => 'Manual']);
        $t2 = $this->createMailTemplate(['name' => 'T2', 'mail_class' => 'NotifySelectStudent', 'active' => false, 'sending_frequency' => 'Manual']);

        $this->from('/mailtemplates')->actingAs($admin)
            ->put('/mailtemplates/'.$t2->id, array_merge($this->payload(), ['name' => 'T2', 'mail_class' => 'NotifySelectStudent', 'active' => true, 'sending_frequency' => 'Manual']))
            ->assertRedirect('/mailtemplates');
        $this->assertSessionHasWarningContaining('disparo manual');
        $this->assertSame(0, (int) $t2->fresh()->active);
    }

    public function test_cen_mailtemplate_007_frequencia_manual_limpa_data_e_hora()
    {
        $admin = $this->admin();
        $t = $this->createMailTemplate(['sending_frequency' => 'Mensal', 'sending_date' => '15', 'sending_hour' => '08:00']);

        $this->from('/mailtemplates')->actingAs($admin)
            ->put('/mailtemplates/'.$t->id, array_merge($this->payload(), ['name' => $t->name, 'sending_frequency' => 'Manual']))
            ->assertRedirect('/mailtemplates');

        $t->refresh();
        $this->assertNull($t->sending_date);
        $this->assertNull($t->sending_hour);
    }

    public function test_cen_mailtemplate_008_activate_bloqueia_dois_manuais_ativos_da_mesma_classe()
    {
        $admin = $this->admin();
        $t1 = $this->createMailTemplate(['name' => 'T1', 'mail_class' => 'NotifySelectStudent', 'active' => true, 'sending_frequency' => 'Manual']);
        $t2 = $this->createMailTemplate(['name' => 'T2', 'mail_class' => 'NotifySelectStudent', 'active' => false, 'sending_frequency' => 'Manual']);

        $this->from('/mailtemplates')->actingAs($admin)->get('/mailtemplates/activate/'.$t2->id);
        $this->assertSessionHasWarningContaining('disparo manual');
        $this->assertSame(0, (int) $t2->fresh()->active);
    }

    public function test_cen_mailtemplate_009_activate_define_active_true_em_condicoes_validas()
    {
        $admin = $this->admin();
        $t2 = $this->createMailTemplate(['name' => 'T2', 'mail_class' => 'NotifyInstructorAboutEvaluation', 'active' => false, 'sending_frequency' => 'Manual']);

        $this->from('/mailtemplates')->actingAs($admin)->get('/mailtemplates/activate/'.$t2->id);

        $this->assertSame(1, (int) $t2->fresh()->active);
    }

    public function test_cen_mailtemplate_010_deactivate_define_active_false()
    {
        $admin = $this->admin();
        $t = $this->createMailTemplate(['active' => true]);

        $this->from('/mailtemplates')->actingAs($admin)->get('/mailtemplates/deactivate/'.$t->id);

        $this->assertSame(0, (int) $t->fresh()->active);
    }

    public function test_cen_mailtemplate_011_test_envia_email_de_exemplo_com_registro_real()
    {
        $admin = $this->admin();
        $template = $this->createMailTemplate(['mail_class' => 'NotifySelectStudent', 'active' => true]);

        // cria seleção real para ser usada de exemplo
        $env = $this->seedEnvironment();
        $this->createSelection(['enrollment_id' => $env['enrollment']->id]);

        Mail::fake();

        $this->actingAs($admin)->from('/mailtemplates')
            ->post('/mailtemplates/test', ['mailtemplate_id' => $template->id, 'email' => 'destino@example.com']);

        Mail::assertQueued(\App\Mail\NotifySelectStudent::class);
        $this->assertTrue($this->app['session']->has('alert-info') || str_contains((string) $this->app['session']->get('alert-info'), 'enviado com sucesso'));
    }

    public function test_cen_mailtemplate_012_test_sem_registros_reais_falha_graciosamente()
    {
        $admin = $this->admin();
        $template = $this->createMailTemplate(['mail_class' => 'NotifySelectStudent']);

        // sem nenhuma seleção
        $this->from('/mailtemplates')->actingAs($admin)
            ->post('/mailtemplates/test', ['mailtemplate_id' => $template->id, 'email' => 'destino@example.com']);

        $this->assertSessionHasWarningContaining('nenhum monitor');
    }

    public function test_cen_mailtemplate_013_destroy_exclui_o_modelo()
    {
        $admin = $this->admin();
        $t = $this->createMailTemplate();

        $this->from('/mailtemplates')->actingAs($admin)->delete('/mailtemplates/'.$t->id);

        $this->assertDatabaseMissing('mail_templates', ['id' => $t->id]);
    }
}
