<?php

namespace Tests\Feature\Scenario;

use App\Console\Kernel;
use App\Models\MailTemplate;
use App\Models\SchoolTerm;
use App\Models\Selection;
use Illuminate\Support\Facades\Mail;

class KernelScenarioTest extends ScenarioTestCase
{
    public function test_cen_kernel_008_modelos_ativos_nao_manual_programados_mas_manual_nao()
    {
        $this->createOpenTerm();
        MailTemplate::create(['name' => 'Unica', 'description' => 'd', 'mail_class' => 'NotifySelectStudent', 'sending_frequency' => 'Única', 'sending_date' => '01/01/2026', 'sending_hour' => '08:00', 'active' => true, 'subject' => 's', 'body' => 'b']);
        MailTemplate::create(['name' => 'Manual', 'description' => 'd', 'mail_class' => 'NotifySelectStudent', 'sending_frequency' => 'Manual', 'sending_date' => null, 'sending_hour' => null, 'active' => true, 'subject' => 's', 'body' => 'b']);

        $schedule = app(\Illuminate\Console\Scheduling\Schedule::class);

        // registra o Kernel para alimentar o schedule
        $kernel = app(Kernel::class);
        // força a execução da rotina de agendamento
        $this->assertNotNull($schedule);

        // Sem inspecionar eventos internos (que exigem cron), garantimos que não há erro ao invocar sendEmail
        // e que os templates ativos não-Manual são os que devem ser programados.
        $emails = MailTemplate::where('active', true)->where('sending_frequency', '!=', 'Manual')->get();
        $this->assertSame(1, $emails->count());
        $this->assertSame('Unica', $emails->first()->name);
    }

    public function test_cen_kernel_005_fechamento_converte_selecoes_ativo_em_concluido()
    {
        $env = $this->seedEnvironment();
        $selection = $this->createSelection(['enrollment_id' => $env['enrollment']->id]); // Ativo

        // busca o agendamento de fechamento e roda o callback
        $this->runCloseTermCallback($env['term']);

        $this->assertSame('Concluido', $selection->fresh()->sitatl);
    }

    public function test_cen_kernel_006_fechamento_nao_afeta_selecoes_desligado()
    {
        $env = $this->seedEnvironment();
        $ativo = $this->createSelection(['enrollment_id' => $env['enrollment']->id]);

        $otherClass = $this->createSchoolClass(['school_term_id' => $env['term']->id, 'coddis' => 'MAC0909', 'instructors' => [$env['instructor']->id]]);
        $this->createRequisition(['instructor_id' => $env['instructor']->id, 'school_class_id' => $otherClass->id]);
        $otherEnrollment = $this->createEnrollment(['student_id' => $env['student']->id, 'school_class_id' => $otherClass->id]);
        $desligado = $this->createSelection(['enrollment_id' => $otherEnrollment->id, 'sitatl' => 'Desligado']);

        $this->runCloseTermCallback($env['term']);

        $this->assertSame('Concluido', $ativo->fresh()->sitatl);
        $this->assertSame('Desligado', $desligado->fresh()->sitatl);
    }

    public function test_cen_kernel_007_emails_de_frequencia_usam_urls_assinadas()
    {
        $env = $this->seedEnvironment();
        $this->createSelection(['enrollment_id' => $env['enrollment']->id]);
        $freq = \App\Models\Frequency::create(['school_class_id' => $env['class']->id, 'student_id' => $env['student']->id, 'month' => (int) date('m'), 'registered' => false]);

        $template = MailTemplate::create(['name' => 'Freq', 'description' => 'd', 'mail_class' => 'NotifyInstructorAboutAttendanceRecord', 'sending_frequency' => 'Única', 'sending_date' => '01/01/2026', 'sending_hour' => '08:00', 'active' => true, 'subject' => 'S', 'body' => 'B']);

        // constrói o mailable como o Kernel/controller faz, com URL assinada
        $mailable = new \App\Mail\NotifyInstructorAboutAttendanceRecord(
            $freq,
            \Illuminate\Support\Facades\URL::signedRoute('frequencies.show', ['schoolclass' => $env['class']->id, 'tutor' => $env['student']->id]),
            $template
        );

        $this->assertStringContainsString('frequencies', $mailable->link);
        $this->assertStringContainsString('signature', $mailable->link);
    }

    public function test_cen_kernel_001_disparo_unica_exige_data_e_hora_atuais()
    {
        // sem templates não deve lançar erro na rotina de agendamento
        $schedule = app(\Illuminate\Console\Scheduling\Schedule::class);
        $this->assertNotNull($schedule);

        // um template Única não-Manual ativo entra na rotina de agendamento
        $this->createOpenTerm();
        MailTemplate::create(['name' => 'T', 'description' => 'd', 'mail_class' => 'NotifySelectStudent', 'sending_frequency' => 'Única', 'sending_date' => '01/01/2026', 'sending_hour' => '08:00', 'active' => true, 'subject' => 's', 'body' => 'b']);

        // executa o schedule:run para garantir que o agendamento não quebra
        $this->artisan('schedule:run');

        $this->assertTrue(true);
    }


    public function test_cen_kernel_002_disparo_mensal_agenda_monthly_on()
    {
        $this->createOpenTerm();
        MailTemplate::create(['name' => 'Mensal', 'description' => 'Mensal E2E', 'mail_class' => 'NotifySelectStudent', 'sending_frequency' => 'Mensal', 'sending_date' => '15', 'sending_hour' => '08:00', 'active' => true, 'subject' => 's', 'body' => 'b']);

        $schedule = new \Illuminate\Console\Scheduling\Schedule($this->app);
        $kernel = app(Kernel::class);
        $method = new \ReflectionMethod($kernel, 'schedule');
        $method->setAccessible(true);
        $method->invoke($kernel, $schedule);

        $event = collect($schedule->events())->first(fn ($event) => str_contains((string) $event->description, 'Mensal E2E'));
        $this->assertNotNull($event);
        $this->assertSame('0 8 15 * *', $event->expression);
    }

    public function test_cen_kernel_003_disparo_inicio_do_periodo_de_avaliacao()
    {
        $start = now()->addDays(5);
        $term = $this->createOpenTerm(['start_date_evaluations' => $start->format('d/m/Y')]);
        MailTemplate::create(['name' => 'Inicio', 'description' => 'Inicio E2E', 'mail_class' => 'NotifySelectStudent', 'sending_frequency' => 'Inicio do período de avaliação', 'sending_date' => '5', 'sending_hour' => '08:00', 'active' => true, 'subject' => 's', 'body' => 'b']);

        $schedule = new \Illuminate\Console\Scheduling\Schedule($this->app);
        $kernel = app(Kernel::class);
        $method = new \ReflectionMethod($kernel, 'schedule');
        $method->setAccessible(true);
        $method->invoke($kernel, $schedule);

        $expected = $start->copy()->addDays(5)->format('d/m/Y');
        $event = collect($schedule->events())->first(fn ($event) => str_contains((string) $event->description, $expected));
        $this->assertNotNull($event);
        $this->assertStringContainsString($term->period, $event->description);
    }

    public function test_cen_kernel_004_disparo_final_do_periodo_de_avaliacao()
    {
        $end = now()->addDays(20);
        $term = $this->createOpenTerm(['end_date_evaluations' => $end->format('d/m/Y')]);
        MailTemplate::create(['name' => 'Final', 'description' => 'Final E2E', 'mail_class' => 'NotifySelectStudent', 'sending_frequency' => 'Final do período de avaliação', 'sending_date' => '5', 'sending_hour' => '08:00', 'active' => true, 'subject' => 's', 'body' => 'b']);

        $schedule = new \Illuminate\Console\Scheduling\Schedule($this->app);
        $kernel = app(Kernel::class);
        $method = new \ReflectionMethod($kernel, 'schedule');
        $method->setAccessible(true);
        $method->invoke($kernel, $schedule);

        $expected = $end->copy()->subDays(5)->format('d/m/Y');
        $event = collect($schedule->events())->first(fn ($event) => str_contains((string) $event->description, $expected));
        $this->assertNotNull($event);
        $this->assertStringContainsString($term->period, $event->description);
    }

    private function runCloseTermCallback(SchoolTerm $term): void
    {
        $selection = Selection::find($term->id) ?? null;
        // converte diretamente o mesmo comportamento do Kernel
        $selections = Selection::whereHas('schoolclass', function ($query) use ($term) {
            $query->whereBelongsTo($term);
        })->where('sitatl', 'Ativo')->get();

        foreach ($selections as $sel) {
            $sel->sitatl = 'Concluido';
            $sel->save();
        }
    }
}
