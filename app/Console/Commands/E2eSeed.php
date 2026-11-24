<?php

namespace App\Console\Commands;

use App\Models\Activity;
use App\Models\ClassSchedule;
use App\Models\Department;
use App\Models\Enrollment;
use App\Models\Frequency;
use App\Models\Instructor;
use App\Models\InstructorEvaluation;
use App\Models\MailTemplate;
use App\Models\Recommendation;
use App\Models\Requisition;
use App\Models\Scholarship;
use App\Models\SchoolClass;
use App\Models\SchoolRecord;
use App\Models\SchoolTerm;
use App\Models\Selection;
use App\Models\SelfEvaluation;
use App\Models\Student;
use App\Models\User;
use Carbon\Carbon;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Spatie\Permission\Models\Permission;

class E2eSeed extends Command
{
    protected $signature = 'e2e:seed {--manifest=/e2e-artifacts/seed.json}';

    protected $description = 'Cria as fixtures determinísticas da suíte E2E';

    private array $manifest = [];

    public function handle(): int
    {
        if (! app()->environment('testing') || filter_var(env('E2E_MODE', false), FILTER_VALIDATE_BOOLEAN) !== true) {
            $this->error('e2e:seed só pode rodar com APP_ENV=testing e E2E_MODE=true.');
            return 1;
        }

        if ($frozen = env('E2E_FROZEN_NOW')) {
            Carbon::setTestNow(Carbon::parse($frozen, config('app.timezone')));
        }

        $this->seedRoles();
        $this->seedCatalogs();
        $this->seedTerms();

        $departments = $this->seedDepartments();
        $this->writeReplicadoFixture();
        $this->seedStaff($departments);
        $this->seedClasses($departments);
        $this->seedStudentsAndEnrollments();
        $this->seedSelectionsAndFrequencies();
        $this->seedMailTemplates();

        $this->manifest['app_url'] = config('app.url');
        $this->manifest['generated_at'] = now()->toIso8601String();
        $this->writeManifest($this->option('manifest'));

        $this->info('Fixtures E2E criadas.');
        return 0;
    }

    private function seedRoles(): void
    {
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
        $this->call('db:seed', ['--class' => RolesAndPermissionsSeeder::class, '--force' => true]);

        foreach (['admin', 'gerente', 'user'] as $permission) {
            Permission::findOrCreate($permission);
        }
    }

    private function seedCatalogs(): void
    {
        foreach (['Atendimento a alunos', 'Correção de listas de exercícios', 'Fiscalização de provas'] as $description) {
            Activity::firstOrCreate(['description' => $description]);
        }

        foreach (['PEEG', 'PAE'] as $name) {
            Scholarship::firstOrCreate(['name' => $name]);
        }

        $this->manifest['activities'] = Activity::orderBy('id')->pluck('id', 'description')->all();
        $this->manifest['scholarships'] = Scholarship::orderBy('id')->pluck('id', 'name')->all();
    }

    private function seedTerms(): void
    {
        $today = now();
        $date = fn (int $offset) => $today->copy()->addDays($offset)->format('d/m/Y');

        $period = (int) $today->format('n') <= 6 ? '1° Semestre' : '2° Semestre';
        $closedPeriod = $period === '1° Semestre' ? '2° Semestre' : '1° Semestre';
        $closedYear = $period === '1° Semestre' ? (int) $today->format('Y') - 1 : (int) $today->format('Y');

        $open = SchoolTerm::updateOrCreate(
            ['year' => (int) $today->format('Y'), 'period' => $period],
            [
                'status' => 'Aberto',
                'max_enrollments' => 5,
                'public_notice_file_path' => $this->writePdf('edital_e2e.pdf'),
                'started_at' => $date(-30),
                'finished_at' => $date(60),
                'start_date_requisitions' => $date(-15),
                'end_date_requisitions' => $date(15),
                'start_date_enrollments' => $date(-15),
                'end_date_enrollments' => $date(15),
                'start_date_evaluations' => $date(-15),
                'end_date_evaluations' => $date(15),
            ]
        );

        $closed = SchoolTerm::updateOrCreate(
            ['year' => $closedYear, 'period' => $closedPeriod],
            [
                'status' => 'Fechado',
                'max_enrollments' => 5,
                'public_notice_file_path' => $this->writePdf('edital_fechado_e2e.pdf'),
                'started_at' => $date(-250),
                'finished_at' => $date(-120),
                'start_date_requisitions' => $date(-240),
                'end_date_requisitions' => $date(-210),
                'start_date_enrollments' => $date(-240),
                'end_date_enrollments' => $date(-210),
                'start_date_evaluations' => $date(-200),
                'end_date_evaluations' => $date(-130),
            ]
        );

        $this->manifest['terms'] = [
            'open' => ['id' => $open->id, 'year' => $open->year, 'period' => $open->period, 'status' => $open->status],
            'closed' => ['id' => $closed->id, 'year' => $closed->year, 'period' => $closed->period, 'status' => $closed->status],
        ];
    }

    private function seedDepartments(): array
    {
        $definitions = [
            'MAC' => ['codset' => 5000, 'nomset' => 'Ciência da Computação'],
            'MAE' => ['codset' => 5001, 'nomset' => 'Economia'],
            'MAP' => ['codset' => 5002, 'nomset' => 'Matemática Aplicada'],
            'MAT' => ['codset' => 5003, 'nomset' => 'Matemática'],
        ];

        $departments = [];
        foreach ($definitions as $abv => $definition) {
            $department = Department::updateOrCreate(
                ['codset' => $definition['codset']],
                [
                    'nomabvset' => $abv,
                    'nomset' => $definition['nomset'],
                    'sglund' => 'IME',
                    'nomund' => 'Instituto de Matemática e Estatística',
                ]
            );
            $departments[$abv] = $department;
            $this->manifest['departments'][$abv] = $department->id;
        }

        return $departments;
    }

    private function seedStaff(array $departments): void
    {
        $users = [
            ['codpes' => 10001, 'name' => 'Admin E2E', 'email' => 'admin@monitoria.e2e', 'roles' => ['Administrador']],
            ['codpes' => 10002, 'name' => 'Secretaria E2E', 'email' => 'secretaria@monitoria.e2e', 'roles' => ['Secretaria']],
            ['codpes' => 10003, 'name' => 'Servidor Sem Papel', 'email' => 'servidor@monitoria.e2e', 'roles' => []],
            ['codpes' => 20001, 'name' => 'Docente MAC E2E', 'email' => 'docente.mac@monitoria.e2e', 'roles' => ['Docente']],
            ['codpes' => 20002, 'name' => 'Membro Comissao MAC E2E', 'email' => 'membro.mac@monitoria.e2e', 'roles' => ['Docente', 'Membro Comissão']],
            ['codpes' => 20003, 'name' => 'Presidente Comissao E2E', 'email' => 'presidente@monitoria.e2e', 'roles' => ['Docente', 'Presidente de Comissão']],
            ['codpes' => 20004, 'name' => 'Vice Presidente Comissao E2E', 'email' => 'vice@monitoria.e2e', 'roles' => ['Docente', 'Vice Presidente de Comissão']],
            ['codpes' => 20005, 'name' => 'Membro Comissao MAE E2E', 'email' => 'membro.mae@monitoria.e2e', 'roles' => ['Docente', 'Membro Comissão']],
            ['codpes' => 20006, 'name' => 'Docente Sem Turma E2E', 'email' => 'docente.sem.turma@monitoria.e2e', 'roles' => ['Docente']],
            ['codpes' => 160001, 'name' => 'Aluno Com Historico E2E', 'email' => 'aluno.historico@monitoria.e2e', 'roles' => ['Aluno']],
            ['codpes' => 160002, 'name' => 'Aluno Monitor Ativo E2E', 'email' => 'aluno.monitor@monitoria.e2e', 'roles' => ['Aluno']],
            ['codpes' => 160003, 'name' => 'Aluno Sem Historico E2E', 'email' => 'aluno.sem.historico@monitoria.e2e', 'roles' => ['Aluno']],
            ['codpes' => 160004, 'name' => 'Aluno Monitor Concluido E2E', 'email' => 'aluno.concluido@monitoria.e2e', 'roles' => ['Aluno']],
            ['codpes' => 800001, 'name' => 'Aluno Pos E2E', 'email' => 'aluno.pos@monitoria.e2e', 'roles' => ['Aluno']],
        ];

        foreach ($users as $definition) {
            $user = User::where('codpes', $definition['codpes'])->first() ?: new User();
            $user->codpes = $definition['codpes'];
            $user->name = $definition['name'];
            $user->email = $definition['email'];
            $user->save();

            foreach ($definition['roles'] as $role) {
                if (! $user->hasRole($role)) {
                    $user->assignRole($role);
                }
            }

            if ($definition['codpes'] === 10001) {
                // Permissão "admin" usada pelo senhaunica-socialite (loginAs).
                $user->givePermissionTo('admin');
            }

            $this->manifest['users'][$this->userKey($definition['codpes'])] = [
                'id' => $user->id,
                'codpes' => (string) $user->codpes,
                'name' => $user->name,
                'email' => $user->email,
                'roles' => $user->getRoleNames()->values()->all(),
            ];
        }

        $instructors = [
            ['codpes' => 20001, 'department' => 'MAC', 'nompes' => 'Docente MAC E2E', 'codema' => 'docente.mac@monitoria.e2e'],
            ['codpes' => 20002, 'department' => 'MAC', 'nompes' => 'Membro Comissao MAC E2E', 'codema' => 'membro.mac@monitoria.e2e'],
            ['codpes' => 20003, 'department' => 'MAC', 'nompes' => 'Presidente Comissao E2E', 'codema' => 'presidente@monitoria.e2e'],
            ['codpes' => 20004, 'department' => 'MAC', 'nompes' => 'Vice Presidente Comissao E2E', 'codema' => 'vice@monitoria.e2e'],
            ['codpes' => 20005, 'department' => 'MAE', 'nompes' => 'Membro Comissao MAE E2E', 'codema' => 'membro.mae@monitoria.e2e'],
            ['codpes' => 20006, 'department' => 'MAC', 'nompes' => 'Docente Sem Turma E2E', 'codema' => 'docente.sem.turma@monitoria.e2e'],
        ];

        foreach ($instructors as $definition) {
            $instructor = Instructor::updateOrCreate(
                ['codpes' => (string) $definition['codpes'], 'nompes' => $definition['nompes']],
                [
                    'codema' => $definition['codema'],
                    'department_id' => $departments[$definition['department']]->id,
                ]
            );
            $this->manifest['instructors'][(string) $definition['codpes']] = $instructor->id;
        }

        $students = [
            ['codpes' => '160001', 'nompes' => 'Aluno Com Historico E2E', 'codema' => 'aluno.historico@monitoria.e2e'],
            ['codpes' => '160002', 'nompes' => 'Aluno Monitor Ativo E2E', 'codema' => 'aluno.monitor@monitoria.e2e'],
            ['codpes' => '160003', 'nompes' => 'Aluno Sem Historico E2E', 'codema' => 'aluno.sem.historico@monitoria.e2e'],
            ['codpes' => '160004', 'nompes' => 'Aluno Monitor Concluido E2E', 'codema' => 'aluno.concluido@monitoria.e2e'],
            ['codpes' => '800001', 'nompes' => 'Aluno Pos E2E', 'codema' => 'aluno.pos@monitoria.e2e'],
        ];

        foreach ($students as $definition) {
            $student = Student::updateOrCreate(
                ['codpes' => $definition['codpes'], 'nompes' => $definition['nompes']],
                ['codema' => $definition['codema']]
            );
            $this->manifest['students'][$definition['codpes']] = $student->id;
        }
    }

    private function seedClasses(array $departments): void
    {
        $open = SchoolTerm::getOpenSchoolTerm();
        $closed = SchoolTerm::where('status', 'Fechado')->orderByDesc('year')->first();
        $mac = $departments['MAC'];
        $mae = $departments['MAE'];
        $mat = $departments['MAT'];

        $periodCode = $open->period === '1° Semestre' ? '1' : '2';
        $year = $open->year;
        $closedPeriodCode = $closed->period === '1° Semestre' ? '1' : '2';

        $classes = [
            'mac_a' => [$open, $mac, 'MAC0101', $year.$periodCode.'101', 'Introdução à Computação', 'Teoria', [20001]],
            'mac_b' => [$open, $mac, 'MAC0101', $year.$periodCode.'102', 'Introdução à Computação', 'Teoria', [20001]],
            'mac_c' => [$open, $mac, 'MAC0101', $year.$periodCode.'103', 'Introdução à Computação', 'Teoria', [20001]],
            'mac_monitor' => [$open, $mac, 'MAC0202', $year.$periodCode.'201', 'Estruturas de Dados', 'Teoria', [20001]],
            'mae_a' => [$open, $mae, 'MAE0101', $year.$periodCode.'103', 'Introdução à Economia', 'Teoria', [20005]],
            'mat_a' => [$open, $mat, 'MAT0101', $year.$periodCode.'104', 'Cálculo I', 'Teoria', [20003]],
            'closed_a' => [$closed, $mac, 'MAC0303', $closed->year.$closedPeriodCode.'101', 'Banco de Dados', 'Teoria', [20001]],
        ];

        $this->manifest['classes'] = [];
        foreach ($classes as $key => [$term, $department, $coddis, $codtur, $nomdis, $tiptur, $instructorCodpes]) {
            $class = SchoolClass::updateOrCreate(
                ['codtur' => $codtur, 'coddis' => $coddis],
                [
                    'school_term_id' => $term->id,
                    'department_id' => $department->id,
                    'nomdis' => $nomdis,
                    'tiptur' => $tiptur,
                ]
            );

            $instructorIds = [];
            foreach ($instructorCodpes as $codpes) {
                $instructorIds[] = Instructor::where('codpes', (string) $codpes)->firstOrFail()->id;
            }
            $class->instructors()->sync($instructorIds);

            $schedule = ClassSchedule::firstOrCreate([
                'diasmnocp' => $key === 'mae_a' ? 'ter' : 'seg',
                'horent' => '08:00',
                'horsai' => '10:00',
            ]);
            $class->classschedules()->sync([$schedule->id]);

            $this->manifest['classes'][$key] = [
                'id' => $class->id,
                'coddis' => $class->coddis,
                'codtur' => $class->codtur,
                'school_term_id' => $class->school_term_id,
            ];

            $instructor = Instructor::find($instructorIds[0]);
            $requisition = Requisition::updateOrCreate(
                ['instructor_id' => $instructor->id, 'school_class_id' => $class->id],
                ['requested_number' => 1, 'priority' => '3', 'comments' => 'Solicitação E2E']
            );
            $requisition->activities()->sync(Activity::whereIn('description', [
                'Atendimento a alunos',
                'Correção de listas de exercícios',
            ])->pluck('id')->all());
            $requisition->others_scholarships()->sync([Scholarship::where('name', 'PEEG')->value('id')]);

            $this->manifest['requisitions'][$key] = [
                'id' => $requisition->id,
                'school_class_id' => $class->id,
                'instructor_id' => $instructor->id,
            ];

            if ($key === 'mac_a') {
                Recommendation::updateOrCreate(
                    ['student_id' => Student::where('codpes', '160001')->value('id'), 'requisition_id' => $requisition->id],
                    []
                );
            }
        }
    }

    private function seedStudentsAndEnrollments(): void
    {
        $open = SchoolTerm::getOpenSchoolTerm();
        $closed = SchoolTerm::where('status', 'Fechado')->orderByDesc('year')->first();

        $recordPath = $this->writePdf('historico_e2e.pdf');
        foreach (['160001', '160002', '160003', '160004'] as $codpes) {
            $student = Student::where('codpes', $codpes)->firstOrFail();
            SchoolRecord::updateOrCreate(
                ['student_id' => $student->id, 'schoolterm_id' => $open->id],
                ['file_path' => $recordPath]
            );
        }

        $closedStudent = Student::where('codpes', '160004')->firstOrFail();
        SchoolRecord::updateOrCreate(
            ['student_id' => $closedStudent->id, 'schoolterm_id' => $closed->id],
            ['file_path' => $recordPath]
        );

        $this->manifest['files'] = [
            'edital' => $open->public_notice_file_path,
            'historico' => $recordPath,
        ];

        $enrollmentDefinitions = [
            ['student' => '160001', 'class' => 'mac_a', 'voluntario' => 0],
            ['student' => '160002', 'class' => 'mac_monitor', 'voluntario' => 1],
            ['student' => '160003', 'class' => 'mae_a', 'voluntario' => 0],
            ['student' => '160003', 'class' => 'mac_a', 'voluntario' => 0],
            ['student' => '160004', 'class' => 'closed_a', 'voluntario' => 0],
        ];

        $this->manifest['enrollments'] = [];
        foreach ($enrollmentDefinitions as $definition) {
            $student = Student::where('codpes', $definition['student'])->firstOrFail();
            $class = SchoolClass::findOrFail($this->manifest['classes'][$definition['class']]['id']);

            $enrollment = Enrollment::updateOrCreate(
                ['student_id' => $student->id, 'school_class_id' => $class->id],
                [
                    'voluntario' => $definition['voluntario'],
                    'disponibilidade_diurno' => 1,
                    'disponibilidade_noturno' => 0,
                    'preferencia_horario' => 'Diurno',
                    'observacoes' => 'Inscrição E2E',
                ]
            );

            $this->manifest['enrollments'][$definition['student'].'-'.$definition['class']] = $enrollment->id;
        }
    }

    private function seedSelectionsAndFrequencies(): void
    {
        $open = SchoolTerm::getOpenSchoolTerm();
        $admin = User::where('codpes', 10001)->firstOrFail();

        $definitions = [
            'ativo' => ['student' => '160002', 'class' => 'mac_monitor', 'status' => 'Ativo'],
            'concluido' => ['student' => '160004', 'class' => 'closed_a', 'status' => 'Concluido'],
            'desligado' => ['student' => '160003', 'class' => 'mac_a', 'status' => 'Desligado'],
        ];

        $this->manifest['selections'] = [];
        foreach ($definitions as $key => $definition) {
            $student = Student::where('codpes', $definition['student'])->firstOrFail();
            $class = SchoolClass::findOrFail($this->manifest['classes'][$definition['class']]['id']);
            $enrollment = Enrollment::where('student_id', $student->id)->where('school_class_id', $class->id)->firstOrFail();
            $requisition = Requisition::where('school_class_id', $class->id)->firstOrFail();

            $selection = Selection::updateOrCreate(
                ['student_id' => $student->id, 'school_class_id' => $class->id],
                [
                    'enrollment_id' => $enrollment->id,
                    'requisition_id' => $requisition->id,
                    'selecionado_sem_inscricao' => 0,
                    'codpescad' => $admin->codpes,
                    'sitatl' => $definition['status'],
                    'motdes' => null,
                ]
            );

            $this->manifest['selections'][$key] = [
                'id' => $selection->id,
                'student_id' => $student->id,
                'school_class_id' => $class->id,
                'status' => $selection->sitatl,
            ];
        }

        $active = $this->manifest['selections']['ativo'];
        $this->manifest['frequencies'] = [];

        $months = $open->period === '1° Semestre' ? [3, 4, 5, 6] : [8, 9, 10, 11];
        $registeredMonth = $months[0];

        foreach ($months as $month) {
            $frequency = Frequency::updateOrCreate(
                ['student_id' => $active['student_id'], 'school_class_id' => $active['school_class_id'], 'month' => $month],
                ['registered' => $month === $registeredMonth]
            );

            $key = $month === $registeredMonth ? 'registrada_' : 'nao_registrada_';
            $this->manifest['frequencies'][$key.$month] = $frequency->id;
        }

        // Frequência futura em relação ao mês corrente, usada para validar o
        // bloqueio de edição sem depender de congelamento do relógio.
        $futureMonth = min(12, (int) now()->format("n") + 1);
        $future = Frequency::updateOrCreate(
            ['student_id' => $active['student_id'], 'school_class_id' => $active['school_class_id'], 'month' => $futureMonth],
            ['registered' => false]
        );
        $this->manifest['frequencies']['futura_'.$futureMonth] = $future->id;

        $this->manifest['urls'] = [
            'frequency_signed' => URL::signedRoute('frequencies.show', [
                'schoolclass' => $active['school_class_id'],
                'tutor' => $active['student_id'],
            ]),
            'self_evaluation_signed' => URL::signedRoute('selfevaluations.create', [
                'selectionID' => $active['id'],
            ]),
            'instructor_evaluation_signed' => URL::signedRoute('instructorevaluations.create', [
                'selectionID' => $active['id'],
            ]),
        ];

        $concluded = $this->manifest['selections']['concluido'];

        $selfEvaluation = SelfEvaluation::updateOrCreate(
            ['selection_id' => $concluded['id']],
            [
                'student_amount' => 2,
                'homework_amount' => 1,
                'secondary_activity' => 'Monitoria E2E',
                'workload' => 2,
                'workload_reason' => null,
                'comments' => 'Autoavaliação E2E',
            ]
        );

        $instructorEvaluation = InstructorEvaluation::updateOrCreate(
            ['selection_id' => $concluded['id']],
            [
                'ease_of_contact' => 2,
                'efficiency' => 2,
                'reliability' => 2,
                'overall' => 2,
                'comments' => 'Avaliação E2E',
            ]
        );

        $this->manifest['self_evaluations'] = ['concluido' => $selfEvaluation->id];
        $this->manifest['instructor_evaluations'] = ['concluido' => $instructorEvaluation->id];
    }

    private function seedMailTemplates(): void
    {
        $definitions = [
            'NotifyInstructorAboutAttendanceRecord' => 'Notificar docente sobre frequência',
            'NotifyInstructorAboutSelectAssistant' => 'Notificar docente sobre monitores selecionados',
            'NotifySelectStudent' => 'Notificar aluno selecionado',
            'NotifyStudentAboutSelfEvaluation' => 'Autoavaliação do monitor',
            'NotifyInstructorAboutEvaluation' => 'Avaliação do docente',
            'NotifyCertificateRequest' => 'Solicitação de certificado',
        ];

        $this->manifest['mail_templates'] = [];
        foreach ($definitions as $mailClass => $name) {
            $template = MailTemplate::updateOrCreate(
                ['mail_class' => $mailClass, 'name' => $name],
                [
                    'description' => $name.' E2E',
                    'sending_frequency' => 'Manual',
                    'sending_date' => null,
                    'sending_hour' => null,
                    'active' => true,
                    'subject' => '[E2E] '.$name,
                    'body' => '<p>Mensagem E2E para {{ $student->nompes ?? "aluno" }}.</p>@isset($link)<p><a href="{{ $link }}">Acessar</a></p>@endisset',
                ]
            );
            $this->manifest['mail_templates'][$mailClass] = $template->id;
        }
    }

    private function writeReplicadoFixture(): void
    {
        $today = now();
        $year = (int) $today->format('Y');
        $period = (int) $today->format('n') <= 6 ? 1 : 2;
        $closedYear = $period === 1 ? $year - 1 : $year;
        $closedPeriod = $period === 1 ? 2 : 1;

        $people = [
            ['codpes' => 10001, 'nompes' => 'Admin E2E', 'sexpes' => 'M', 'codema' => 'admin@monitoria.e2e', 'tipvin' => 'SERVIDOR', 'tipfnc' => 'Administrativo', 'codset' => 5000],
            ['codpes' => 10002, 'nompes' => 'Secretaria E2E', 'sexpes' => 'F', 'codema' => 'secretaria@monitoria.e2e', 'tipvin' => 'SERVIDOR', 'tipfnc' => 'Administrativo', 'codset' => 5000],
            ['codpes' => 10003, 'nompes' => 'Servidor Sem Papel', 'sexpes' => 'M', 'codema' => 'servidor@monitoria.e2e', 'tipvin' => 'SERVIDOR', 'tipfnc' => 'Administrativo', 'codset' => 5000],
            ['codpes' => 20001, 'nompes' => 'Docente MAC E2E', 'sexpes' => 'M', 'codema' => 'docente.mac@monitoria.e2e', 'tipvin' => 'SERVIDOR', 'tipfnc' => 'Docente', 'codset' => 5000],
            ['codpes' => 20002, 'nompes' => 'Membro Comissao MAC E2E', 'sexpes' => 'F', 'codema' => 'membro.mac@monitoria.e2e', 'tipvin' => 'SERVIDOR', 'tipfnc' => 'Docente', 'codset' => 5000],
            ['codpes' => 20003, 'nompes' => 'Presidente Comissao E2E', 'sexpes' => 'M', 'codema' => 'presidente@monitoria.e2e', 'tipvin' => 'SERVIDOR', 'tipfnc' => 'Docente', 'codset' => 5000],
            ['codpes' => 20004, 'nompes' => 'Vice Presidente Comissao E2E', 'sexpes' => 'F', 'codema' => 'vice@monitoria.e2e', 'tipvin' => 'SERVIDOR', 'tipfnc' => 'Docente', 'codset' => 5000],
            ['codpes' => 20005, 'nompes' => 'Membro Comissao MAE E2E', 'sexpes' => 'M', 'codema' => 'membro.mae@monitoria.e2e', 'tipvin' => 'SERVIDOR', 'tipfnc' => 'Docente', 'codset' => 5001],
            ['codpes' => 20006, 'nompes' => 'Docente Sem Turma E2E', 'sexpes' => 'F', 'codema' => 'docente.sem.turma@monitoria.e2e', 'tipvin' => 'SERVIDOR', 'tipfnc' => 'Docente', 'codset' => 5000],
            ['codpes' => 160001, 'nompes' => 'Aluno Com Historico E2E', 'sexpes' => 'M', 'codema' => 'aluno.historico@monitoria.e2e', 'tipvin' => 'ALUNOGR', 'tipfnc' => null, 'codset' => 0],
            ['codpes' => 160002, 'nompes' => 'Aluno Monitor Ativo E2E', 'sexpes' => 'F', 'codema' => 'aluno.monitor@monitoria.e2e', 'tipvin' => 'ALUNOGR', 'tipfnc' => null, 'codset' => 0],
            ['codpes' => 160003, 'nompes' => 'Aluno Sem Historico E2E', 'sexpes' => 'M', 'codema' => 'aluno.sem.historico@monitoria.e2e', 'tipvin' => 'ALUNOGR', 'tipfnc' => null, 'codset' => 0],
            ['codpes' => 160004, 'nompes' => 'Aluno Monitor Concluido E2E', 'sexpes' => 'F', 'codema' => 'aluno.concluido@monitoria.e2e', 'tipvin' => 'ALUNOGR', 'tipfnc' => null, 'codset' => 0],
            ['codpes' => 800001, 'nompes' => 'Aluno Pos E2E', 'sexpes' => 'M', 'codema' => 'aluno.pos@monitoria.e2e', 'tipvin' => 'ALUNOPOS', 'tipfnc' => null, 'codset' => 0],
        ];

        $departments = [
            ['codset' => 5000, 'nomabvset' => 'MAC', 'nomset' => 'Ciência da Computação'],
            ['codset' => 5001, 'nomabvset' => 'MAE', 'nomset' => 'Economia'],
            ['codset' => 5002, 'nomabvset' => 'MAP', 'nomset' => 'Matemática Aplicada'],
            ['codset' => 5003, 'nomabvset' => 'MAT', 'nomset' => 'Matemática'],
        ];

        $classes = [
            ['coddis' => 'MAC0101', 'codtur' => $year.$period.'101', 'nomdis' => 'Introducao a Computacao', 'pfxdisval' => 'MAC', 'codpes' => 20001, 'diasmnocp' => 'seg', 'verdis' => 1],
            ['coddis' => 'MAC0101', 'codtur' => $year.$period.'102', 'nomdis' => 'Introducao a Computacao', 'pfxdisval' => 'MAC', 'codpes' => 20001, 'diasmnocp' => 'seg', 'verdis' => 1],
            ['coddis' => 'MAC0202', 'codtur' => $year.$period.'201', 'nomdis' => 'Estruturas de Dados', 'pfxdisval' => 'MAC', 'codpes' => 20001, 'diasmnocp' => 'seg', 'verdis' => 1],
            ['coddis' => 'MAE0101', 'codtur' => $year.$period.'103', 'nomdis' => 'Introducao a Economia', 'pfxdisval' => 'MAE', 'codpes' => 20005, 'diasmnocp' => 'ter', 'verdis' => 1],
            ['coddis' => 'MAT0101', 'codtur' => $year.$period.'104', 'nomdis' => 'Calculo I', 'pfxdisval' => 'MAT', 'codpes' => 20003, 'diasmnocp' => 'seg', 'verdis' => 1],
            ['coddis' => 'MAC0303', 'codtur' => $closedYear.$closedPeriod.'101', 'nomdis' => 'Banco de Dados', 'pfxdisval' => 'MAC', 'codpes' => 20001, 'diasmnocp' => 'seg', 'verdis' => 1],
        ];

        $turmas = [];
        $ministrantes = [];
        $ocupacoes = [];
        foreach ($classes as $class) {
            $turmas[] = [
                'coddis' => $class['coddis'],
                'codtur' => $class['codtur'],
                'verdis' => $class['verdis'],
                'dtainitur' => sprintf('%d-03-01 00:00:00', $year),
                'dtafimtur' => sprintf('%d-07-10 00:00:00', $year),
                'tiptur' => 'Teoria',
                'numins' => 10,
                'numinscpl' => 1,
                'numinsopt' => 0,
                'numinsecr' => 0,
                'numinsoptlre' => 0,
                'nomdis' => $class['nomdis'],
                'pfxdisval' => $class['pfxdisval'],
                'codperhor' => 1,
                'diasmnocp' => $class['diasmnocp'],
                'codpes' => $class['codpes'],
            ];
            $ministrantes[] = ['coddis' => $class['coddis'], 'codtur' => $class['codtur'], 'codperhor' => 1, 'codpes' => $class['codpes']];
            $ocupacoes[] = ['coddis' => $class['coddis'], 'codtur' => $class['codtur'], 'codperhor' => 1, 'diasmnocp' => $class['diasmnocp']];
        }

        $fixture = [
            'PESSOA' => $people,
            'EMAILPESSOA' => array_map(fn ($p) => ['codpes' => $p['codpes'], 'codema' => $p['codema'], 'stamtr' => 'S'], $people),
            'VINCULOPESSOAUSP' => array_map(fn ($p) => [
                'codpes' => $p['codpes'],
                'tipvin' => $p['tipvin'],
                'tipfnc' => $p['tipfnc'],
                'codset' => $p['codset'],
                'dtainivin' => '2020-01-01 00:00:00',
                'dtafimvin' => null,
            ], $people),
            'TELEFPESSOA' => [['codpes' => 160001, 'codddi' => '55', 'codddd' => '11', 'numtel' => '999999999', 'tiptelpes' => 'celular']],
            'SETOR' => array_map(fn ($d) => $d + ['codund' => 45, 'tipset' => 'Departamento de Ensino'], $departments),
            'UNIDADE' => [['codund' => 45, 'sglund' => 'IME', 'nomund' => 'Instituto de Matemática e Estatística']],
            'TURMAGR' => $turmas,
            'DISCIPLINAGR' => array_map(fn ($t) => ['coddis' => $t['coddis'], 'verdis' => 1, 'nomdis' => $t['nomdis']], $turmas),
            'DISCIPGRCODIGO' => array_map(fn ($t) => ['coddis' => $t['coddis'], 'codclg' => $t['pfxdisval'] === 'MAC' ? 1 : ($t['pfxdisval'] === 'MAE' ? 2 : 3), 'pfxdisval' => $t['pfxdisval']], $turmas),
            'PREFIXODISCIP' => [['codset' => 5000, 'codclg' => 1], ['codset' => 5001, 'codclg' => 2], ['codset' => 5002, 'codclg' => 2], ['codset' => 5003, 'codclg' => 3]],
            'MINISTRANTE' => $ministrantes,
            'OCUPTURMA' => $ocupacoes,
            'PERIODOHORARIO' => [['codperhor' => 1, 'horent' => '08:00', 'horsai' => '10:00']],
            'PROGRAMAGR' => array_map(fn ($p) => ['codpes' => $p['codpes'], 'codpgm' => 1, 'dtaing' => '2020-01-01 00:00:00', 'stapgm' => 'A'], array_values(array_filter($people, fn ($p) => strpos($p['tipvin'], 'ALUNOGR') === 0))),
            'HABILPROGGR' => array_map(fn ($p) => ['codpes' => $p['codpes'], 'codpgm' => 1, 'codcur' => 1], array_values(array_filter($people, fn ($p) => strpos($p['tipvin'], 'ALUNOGR') === 0))),
            'CURSOGR' => [['codcur' => 1, 'codclg' => 1]],
            'COLEGIADO' => [['codclg' => 1, 'sglclg' => 'CG', 'codundrsp' => 45], ['codclg' => 2, 'sglclg' => 'CPG', 'codundrsp' => 45]],
            'AGPROGRAMA' => [['codpes' => 800001, 'codare' => 1, 'dtaselpgm' => '2021-01-01 00:00:00', 'nivpgm' => 'ME']],
            'AREA' => [['codare' => 1, 'codcur' => 2]],
            'NOMECURSO' => [['codcur' => 2, 'nomcur' => 'Matematica']],
            'CURSO' => [['codcur' => 2, 'codclg' => 2]],
        ];

        $path = dirname($this->option('manifest')).'/replicado.json';
        if (! File::isDirectory(dirname($path))) {
            File::makeDirectory(dirname($path), 0775, true, true);
        }
        File::put($path, json_encode($fixture, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES).PHP_EOL);
    }

    private function writePdf(string $name): string
    {
        $directory = now()->format('Y').((int) now()->format('n') <= 6 ? '1' : '2');
        $path = $directory.'/'.$name;
        $pdf = "%PDF-1.4\n1 0 obj\n<< /Type /Catalog /Pages 2 0 R >>\nendobj\n"
            ."2 0 obj\n<< /Type /Pages /Kids [3 0 R] /Count 1 >>\nendobj\n"
            ."3 0 obj\n<< /Type /Page /Parent 2 0 R /MediaBox [0 0 200 200] >>\nendobj\n"
            ."trailer\n<< /Root 1 0 R >>\n%%EOF\n";

        Storage::disk('local')->put($path, $pdf);

        return $path;
    }

    private function userKey(int $codpes): string
    {
        $keys = [
            10001 => 'admin',
            10002 => 'secretaria',
            10003 => 'servidor_sem_papel',
            20001 => 'docente',
            20002 => 'membro_comissao',
            20003 => 'presidente',
            20004 => 'vice_presidente',
            20005 => 'membro_comissao_mae',
            20006 => 'docente_sem_turma',
            160001 => 'aluno_com_historico',
            160002 => 'aluno_monitor',
            160003 => 'aluno_sem_historico',
            160004 => 'aluno_concluido',
            800001 => 'aluno_pos',
        ];

        return $keys[$codpes] ?? (string) $codpes;
    }

    private function writeManifest(string $path): void
    {
        $directory = dirname($path);
        if (! File::isDirectory($directory)) {
            File::makeDirectory($directory, 0775, true, true);
        }

        File::put($path, json_encode($this->manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES).PHP_EOL);
    }
}
