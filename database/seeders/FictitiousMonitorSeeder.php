<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Department;
use App\Models\SchoolTerm;
use App\Models\SchoolClass;
use App\Models\Instructor;
use App\Models\Requisition;
use App\Models\Student;
use App\Models\Enrollment;
use App\Models\Selection;
use App\Models\ClassSchedule;
use App\Models\MailTemplate;

class FictitiousMonitorSeeder extends Seeder
{
    /**
     * Cria um monitor fictício e todos os dados necessários para gerar um
     * Certificado/Atestado de Monitoria completo (timbre novo, nome da
     * coordenadora e fluxo com aviso à Secretaria).
     *
     * O seeder reutiliza o período letivo aberto (ou o mais recente) já
     * existente no banco para que o monitor fictício apareça direto na
     * listagem padrão de /certificates. Caso não exista nenhum período
     * letivo, ele cria um (2° Semestre de 2025, fechado).
     *
     * Uso:
     *   php artisan db:seed --class=FictitiousMonitorSeeder
     *
     * Depois acesse /certificates como Secretaria ou Administrador e clique
     * em "Emitir Atestado" na linha do monitor.
     */
    public function run()
    {
        MailTemplate::firstOrCreate(['mail_class' => 'NotifyCertificateRequest'], [
            'name' => 'Notificação de solicitação de certificado de monitoria',
            'description' => 'E-mail enviado à Secretaria sobre solicitação de certificado de monitoria',
            'mail_class' => 'NotifyCertificateRequest',
            'sending_frequency' => 'Manual',
            'sending_date' => null,
            'sending_hour' => null,
            'active' => true,
            'subject' => '[Sistema de Monitoria] Solicitação de Certificado de Monitoria - {{ $student->nompes }}',
            'body' => '<div>Olá,</div>
<div>&nbsp;</div>
<div>
<div>O aluno-monitor <strong>{{ $student->nompes }}</strong> (Nº USP {{ $student->codpes }}) solicitou o Certificado de Monitoria da disciplina <strong>{{ $schoolclass->coddis }} - {{ $schoolclass->nomdis }}</strong>, referente ao <strong>{{ $schoolterm->period }}</strong> de <strong>{{ $schoolterm->year }}</strong>.</div>
</div>
<div>&nbsp;</div>
<div>
<div>Segue em anexo o certificado gerado pelo Sistema de Monitoria. Encaminhe-o ao USP ASSINA para validação e posterior envio ao aluno.</div>
</div>
<div>&nbsp;</div>
<div>
<div>Essa mensagem foi gerada automaticamente pelo <a href="../" target="_blank" rel="noopener">Sistema de Monitoria</a></div>
</div>',
        ]);

        $department = Department::firstOrCreate(
            ['codset' => 5000],
            [
                'nomabvset' => 'MAC',
                'nomset' => 'Departamento de Ciência da Computação',
                'sglund' => 'IME',
                'nomund' => 'Instituto de Matemática e Estatística',
            ]
        );

        $schoolterm = SchoolTerm::getOpenSchoolTerm() ?? SchoolTerm::getLatest();

        if(!$schoolterm){
            $schoolterm = SchoolTerm::create([
                'year' => 2025,
                'period' => '2° Semestre',
                'status' => 'Fechado',
                'max_enrollments' => 5,
                'public_notice_file_path' => '20252/edital_ficticio.pdf',
                'started_at' => '04/08/2025',
                'finished_at' => '19/12/2025',
                'start_date_requisitions' => '01/06/2025',
                'end_date_requisitions' => '15/07/2025',
                'start_date_enrollments' => '15/07/2025',
                'end_date_enrollments' => '01/08/2025',
                'start_date_evaluations' => '10/11/2025',
                'end_date_evaluations' => '19/12/2025',
            ]);
            $this->command->info('Período letivo inexistente: criado o semestre fictício "' . $schoolterm->period . ' de ' . $schoolterm->year . '".');
        }else{
            $this->command->info('Reutilizando o período letivo "' . $schoolterm->period . ' de ' . $schoolterm->year . '" já existente.');
        }

        $year = $schoolterm->year;
        $period = $schoolterm->period == "1° Semestre" ? "1" : "2";
        $codtur = $year . $period . "01";
        $dtainitur = ($period == "1" ? "01/03/" : "01/08/") . $year;
        $dtafimtur = ($period == "1" ? "15/07/" : "15/12/") . $year;

        $instructor = Instructor::updateOrCreate(
            ['codpes' => '9999999', 'nompes' => 'Prof. Dr. Fulano de Tal'],
            [
                'codema' => 'fulano.tal@ime.usp.br',
                'department_id' => $department->id,
            ]
        );

        $schoolclass = SchoolClass::updateOrCreate(
            ['codtur' => $codtur, 'coddis' => 'MAC0110'],
            [
                'school_term_id' => $schoolterm->id,
                'department_id' => $department->id,
                'tiptur' => 'Teoria',
                'nomdis' => 'Introdução à Computação',
                'dtainitur' => $dtainitur,
                'dtafimtur' => $dtafimtur,
            ]
        );
        $schoolclass->instructors()->sync([$instructor->id]);

        $schedule = ClassSchedule::firstOrCreate(['diasmnocp' => 'seg', 'horent' => '08:00', 'horsai' => '10:00']);
        $schoolclass->classschedules()->sync([$schedule->id]);

        $requisition = Requisition::updateOrCreate(
            ['instructor_id' => $instructor->id, 'school_class_id' => $schoolclass->id],
            [
                'requested_number' => 1,
                'priority' => '1',
                'comments' => 'Solicitação fictícia para teste do certificado.',
            ]
        );

        $student = Student::updateOrCreate(
            ['codpes' => '9998777', 'nompes' => 'Aluno Fictício de Teste'],
            [
                'codema' => 'aluno.ficticio@ime.usp.br',
            ]
        );

        $enrollment = Enrollment::updateOrCreate(
            ['student_id' => $student->id, 'school_class_id' => $schoolclass->id],
            [
                'voluntario' => 0,
                'disponibilidade_diurno' => 1,
                'disponibilidade_noturno' => 0,
                'preferencia_horario' => 'Manhã',
                'observacoes' => 'Inscrição fictícia para teste do certificado.',
            ]
        );

        $selection = Selection::updateOrCreate(
            [
                'student_id' => $student->id,
                'school_class_id' => $schoolclass->id,
                'enrollment_id' => $enrollment->id,
                'requisition_id' => $requisition->id,
            ],
            [
                'selecionado_sem_inscricao' => 0,
                'codpescad' => 1,
                'sitatl' => 'Concluido',
                'motdes' => null,
            ]
        );

        $this->command->info('Monitor fictício criado com sucesso!');
        $this->command->info('Semestre: ' . $schoolterm->period . ' de ' . $schoolterm->year);
        $this->command->info('Disciplina: ' . $schoolclass->coddis . ' - ' . $schoolclass->nomdis);
        $this->command->info('Monitor(a): ' . $student->nompes . ' (Nº USP ' . $student->codpes . ')');
        $this->command->info('Acesse /certificates como Secretaria ou Administrador para emitir o atestado.');
    }
}