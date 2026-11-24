<?php

namespace Tests\Feature\Security;

use App\Models\MailTemplate;
use Tests\Feature\Scenario\ScenarioTestCase;

class MailTemplateAuditCommandTest extends ScenarioTestCase
{
    public function test_auditoria_aprova_template_valido(): void
    {
        MailTemplate::create([
            'name' => 'Template válido',
            'description' => 'Template válido',
            'mail_class' => 'NotifySelectStudent',
            'sending_frequency' => 'Manual',
            'active' => true,
            'subject' => 'Turma {{ $schoolclass->coddis }}',
            'body' => '<p>Olá {{ $student->nompes }}</p>',
        ]);

        $this->artisan('mailtemplates:audit')
            ->expectsOutputToContain('Todos os templates passaram')
            ->assertExitCode(0);
    }

    public function test_auditoria_reprova_template_com_php(): void
    {
        MailTemplate::create([
            'name' => 'Template inseguro',
            'description' => 'Template com PHP',
            'mail_class' => 'NotifySelectStudent',
            'sending_frequency' => 'Manual',
            'active' => true,
            'subject' => 'Teste',
            'body' => '@php system("id"); @endphp',
        ]);

        $this->artisan('mailtemplates:audit')
            ->expectsOutputToContain('template(s) com problema')
            ->assertExitCode(1);
    }
}
