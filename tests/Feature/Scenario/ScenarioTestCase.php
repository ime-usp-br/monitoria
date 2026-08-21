<?php

namespace Tests\Feature\Scenario;

use App\Models\Activity;
use App\Models\ClassSchedule;
use App\Models\Department;
use App\Models\Enrollment;
use App\Models\Frequency;
use App\Models\Instructor;
use App\Models\Requisition;
use App\Models\SchoolClass;
use App\Models\SchoolRecord;
use App\Models\SchoolTerm;
use App\Models\Scholarship;
use App\Models\Selection;
use App\Models\Student;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;
use Tests\TestHelpers\ReplicadoStubs;

abstract class ScenarioTestCase extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');

        $this->seed(RolesAndPermissionsSeeder::class);

        // uspdev/senhaunica-socialite's Gate::before querys a real 'admin' permission
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
        \Spatie\Permission\Models\Permission::findOrCreate('admin');

        Activity::firstOrCreate(['description' => 'Atendimento a alunos']);
        Activity::firstOrCreate(['description' => 'Correção de listas de exercícios']);
        Activity::firstOrCreate(['description' => 'Fiscalização de provas']);

        Scholarship::firstOrCreate(['name' => 'PEEG']);
        Scholarship::firstOrCreate(['name' => 'PAE']);

        ReplicadoStubs::install();
    }

    // ------------------------------------------------------------------
    // Users / roles
    // ------------------------------------------------------------------

    protected function createUser(string $role, array $attributes = []): User
    {
        $codpes = $attributes['codpes'] ?? mt_rand(1000000, 9999999);

        $user = User::factory()->create(array_merge([
            'name' => 'Usuario '.$role.' '.$codpes,
            'codpes' => $codpes,
        ], $attributes));

        if ($role) {
            $user->assignRole($role);
        }

        return $user;
    }

    protected function admin(array $attributes = []): User
    {
        return $this->createUser('Administrador', $attributes);
    }

    protected function secretaria(array $attributes = []): User
    {
        return $this->createUser('Secretaria', $attributes);
    }

    protected function docente(array $attributes = [], ?Instructor $instructor = null): User
    {
        $codpes = $attributes['codpes'] ?? mt_rand(1000000, 9999999);

        if (! $instructor) {
            $instructor = $this->createInstructor(['codpes' => $codpes]);
        }

        $attributes['codpes'] = $codpes;

        return $this->createUser('Docente', $attributes);
    }

    protected function aluno(array $attributes = [], ?Student $student = null): User
    {
        $codpes = $attributes['codpes'] ?? mt_rand(1000000, 9999999);

        if (! $student) {
            $student = $this->createStudent(['codpes' => $codpes]);
        }

        $attributes['codpes'] = $codpes;

        return $this->createUser('Aluno', $attributes);
    }

    protected function membroComissao(array $attributes = [], ?Instructor $instructor = null): User
    {
        $codpes = $attributes['codpes'] ?? mt_rand(1000000, 9999999);

        if (! $instructor) {
            $instructor = $this->createInstructor(['codpes' => $codpes, 'department_id' => $this->department('MAC')->id]);
        }

        $attributes['codpes'] = $codpes;

        return $this->createUser('Membro Comissão', $attributes);
    }

    protected function presidente(array $attributes = []): User
    {
        return $this->createUser('Presidente de Comissão', $attributes);
    }

    protected function vicePresidente(array $attributes = []): User
    {
        return $this->createUser('Vice Presidente de Comissão', $attributes);
    }

    // ------------------------------------------------------------------
    // Domain entities
    // ------------------------------------------------------------------

    protected function department(string $abv = 'MAC', ?string $codset = null): Department
    {
        return Department::firstOrCreate(
            ['codset' => $codset ?? (string) mt_rand(4000, 5999)],
            [
                'nomabvset' => $abv,
                'nomset' => 'Departamento '.$abv,
                'sglund' => 'IME',
                'nomund' => 'Instituto de Matematica e Estatistica',
            ]
        );
    }

    protected function createInstructor(array $attributes = []): Instructor
    {
        $dept = $attributes['department_id'] ?? $this->department('MAC')->id;

        return Instructor::create(array_merge([
            'codpes' => (string) mt_rand(10000000, 99999999),
            'nompes' => 'Professor Do Ime',
            'codema' => 'docente@ime.usp.br',
            'department_id' => $dept,
        ], $attributes));
    }

    protected function createStudent(array $attributes = []): Student
    {
        return Student::create(array_merge([
            'codpes' => (string) mt_rand(10000000, 99999999),
            'nompes' => 'Aluno Do Ime',
            'codema' => 'aluno@ime.usp.br',
        ], $attributes));
    }

    /**
     * Creates an "Aberto" SchoolTerm whose requisition/enrollment/evaluation
     * windows include today, so requisition/enrollment flows are allowed.
     */
    protected function createOpenTerm(array $overrides = []): SchoolTerm
    {
        $attributes = $this->openTermAttributes();

        return SchoolTerm::create(array_merge($attributes, $overrides));
    }

    protected function openTermAttributes(): array
    {
        $today = now();

        $date = function ($offsetDays) use ($today) {
            return $today->copy()->addDays($offsetDays)->format('d/m/Y');
        };

        return [
            'year' => (int) $today->format('Y'),
            'period' => '1° Semestre',
            'status' => 'Aberto',
            'max_enrollments' => 5,
            'started_at' => $date(-30),
            'finished_at' => $date(60),
            'start_date_requisitions' => $date(-10),
            'end_date_requisitions' => $date(10),
            'start_date_enrollments' => $date(-10),
            'end_date_enrollments' => $date(10),
            'start_date_evaluations' => $date(-10),
            'end_date_evaluations' => $date(10),
        ];
    }

    protected function createClosedTerm(array $overrides = []): SchoolTerm
    {
        $today = now();
        $date = function ($offsetDays) use ($today) {
            return $today->copy()->addDays($offsetDays)->format('d/m/Y');
        };

        return SchoolTerm::create(array_merge([
            'year' => (int) $today->format('Y') - 1,
            'period' => '2° Semestre',
            'status' => 'Fechado',
            'max_enrollments' => 5,
            'started_at' => $date(-600),
            'finished_at' => $date(-300),
            'start_date_requisitions' => $date(-500),
            'end_date_requisitions' => $date(-400),
            'start_date_enrollments' => $date(-500),
            'end_date_enrollments' => $date(-400),
            'start_date_evaluations' => $date(-400),
            'end_date_evaluations' => $date(-300),
        ], $overrides));
    }

    protected function createSchoolClass(array $attributes = []): SchoolClass
    {
        $term = $attributes['school_term_id'] ?? (SchoolTerm::getOpenSchoolTerm() ?? $this->createOpenTerm())->id;

        $class = SchoolClass::create(array_merge([
            'school_term_id' => $term,
            'department_id' => $this->department('MAC')->id,
            'codtur' => (string) mt_rand(100000, 999999),
            'coddis' => 'MAC'.mt_rand(100, 999),
            'nomdis' => 'Disciplina Teste',
            'tiptur' => 'Teoria',
        ], \Illuminate\Support\Arr::except($attributes, ['instructors', 'skip_schedule'])));

        if (array_key_exists('instructors', $attributes)) {
            $class->instructors()->sync($attributes['instructors']);
        } else {
            $class->instructors()->sync([$this->createInstructor()->id]);
        }

        if (empty($attributes['skip_schedule'])) {
            $schedule = ClassSchedule::firstOrCreate(['diasmnocp' => 'seg', 'horent' => '08:00', 'horsai' => '10:00']);
            $class->classschedules()->sync([$schedule->id]);
        }

        return $class;
    }

    protected function createRequisition(array $attributes = []): Requisition
    {
        $instructor = $attributes['instructor_id']
            ? Instructor::find($attributes['instructor_id'])
            : $this->createInstructor();

        $class = $attributes['school_class_id']
            ? SchoolClass::find($attributes['school_class_id'])
            : $this->createSchoolClass();

        return Requisition::create(array_merge([
            'instructor_id' => $instructor->id,
            'school_class_id' => $class->id,
            'requested_number' => 1,
            'priority' => '1',
            'comments' => null,
        ], $attributes));
    }

    protected function createSchoolRecord(Student $student, SchoolTerm $term, string $path = '20261/historico.pdf'): SchoolRecord
    {
        return SchoolRecord::create([
            'student_id' => $student->id,
            'schoolterm_id' => $term->id,
            'file_path' => $path,
        ]);
    }

    protected function createEnrollment(array $attributes = []): Enrollment
    {
        $student = $attributes['student_id'] ? Student::find($attributes['student_id']) : $this->createStudent();
        $class = $attributes['school_class_id'] ? SchoolClass::find($attributes['school_class_id']) : $this->createSchoolClass();

        return Enrollment::create(array_merge([
            'student_id' => $student->id,
            'school_class_id' => $class->id,
            'voluntario' => 0,
            'disponibilidade_diurno' => 0,
            'disponibilidade_noturno' => 0,
            'preferencia_horario' => 'Manhã',
            'observacoes' => null,
        ], $attributes));
    }

    protected function createSelection(array $attributes = []): Selection
    {
        $enrollment = array_key_exists('enrollment_id', $attributes) ? Enrollment::find($attributes['enrollment_id']) : null;
        if (! $enrollment) {
            $enrollment = $this->createEnrollment(array_intersect_key($attributes, array_flip(['student_id', 'school_class_id'])));
        }

        $class = array_key_exists('school_class_id', $attributes) ? SchoolClass::find($attributes['school_class_id']) : $enrollment->schoolclass;
        $requisition = $attributes['requisition_id'] ?? $class->requisition ?? $this->createRequisition(['school_class_id' => $class->id]);

        return Selection::create(
            array_merge(
                [
                    'student_id' => $enrollment->student_id,
                    'school_class_id' => $class->id,
                    'enrollment_id' => $enrollment->id,
                    'requisition_id' => $requisition->id,
                    'selecionado_sem_inscricao' => 0,
                    'codpescad' => 1,
                    'sitatl' => 'Ativo',
                    'motdes' => null,
                ],
                \Illuminate\Support\Arr::except($attributes, ['dtafimvin', 'selection_id', 'student_id', 'school_class_id', 'enrollment_id', 'requisition_id'])
            )
        );
    }

    protected function createFrequency(array $attributes = []): Frequency
    {
        $selection = array_key_exists('selection_id', $attributes) ? Selection::find($attributes['selection_id']) : $this->createSelection();

        return Frequency::create(array_merge([
            'student_id' => $selection->student_id,
            'school_class_id' => $selection->school_class_id,
            'month' => (int) date('m'),
            'registered' => false,
        ], $attributes));
    }

    /**
     * Seeds a complete fixture environment used by most flows.
     */
    protected function seedEnvironment(array $options = []): array
    {
        $env = [];

        $env['term'] = $this->createOpenTerm(array_key_exists('max_enrollments', $options) ? ['max_enrollments' => $options['max_enrollments']] : []);

        $env['department_mac'] = $this->department('MAC');
        $env['department_mat'] = $this->department('MAT');

        $env['instructor'] = $this->createInstructor(['department_id' => $env['department_mac']->id]);

        $env['docente'] = $this->docente(['codpes' => $env['instructor']->codpes], $env['instructor']);

        $env['secretaria'] = $this->secretaria();
        $env['admin'] = $this->admin();
        $env['presidente'] = $this->presidente();

        $env['membro'] = $this->membroComissao(
            ['codpes' => $env['membro_codpes'] ?? null],
            $this->createInstructor(['department_id' => $env['department_mac']->id])
        );
        $instructorMembro = Instructor::where('department_id', $env['department_mac']->id)->latest('id')->first();
        $env['membro']->codpes = $instructorMembro->codpes;
        $env['membro']->save();

        $instructorMembroMat = $this->createInstructor(['department_id' => $env['department_mat']->id]);
        $env['membro_mat'] = $this->membroComissao(['codpes' => $instructorMembroMat->codpes], $instructorMembroMat);

        $env['class'] = $this->createSchoolClass([
            'school_term_id' => $env['term']->id,
            'department_id' => $env['department_mac']->id,
            'coddis' => 'MAC0101',
            'codtur' => '2026101',
            'instructors' => [$env['instructor']->id],
        ]);

        $env['class_mat'] = $this->createSchoolClass([
            'school_term_id' => $env['term']->id,
            'department_id' => $env['department_mat']->id,
            'coddis' => 'MAT0101',
            'codtur' => '2026102',
            'instructors' => [$this->createInstructor(['department_id' => $env['department_mat']->id])->id],
        ]);

        $env['requisition'] = $this->createRequisition([
            'instructor_id' => $env['instructor']->id,
            'school_class_id' => $env['class']->id,
        ]);

        $env['requisition_mat'] = $this->createRequisition([
            'instructor_id' => $env['class_mat']->instructors()->first()->id,
            'school_class_id' => $env['class_mat']->id,
        ]);

        $env['student'] = $this->createStudent(['codpes' => (string) mt_rand(10000000, 99999999), 'nompes' => 'Aluno Credo']);
        $env['aluno'] = $this->aluno(['codpes' => $env['student']->codpes], $env['student']);

        $env['school_record'] = $this->createSchoolRecord($env['student'], $env['term']);

        if (array_key_exists('enrollment', $options) && $options['enrollment'] === false) {
            return $env;
        }

        $env['enrollment'] = $this->createEnrollment([
            'student_id' => $env['student']->id,
            'school_class_id' => $env['class']->id,
        ]);

        return $env;
    }

    // ------------------------------------------------------------------
    // Assertion helpers
    // ------------------------------------------------------------------

    /**
     * Asserts a flash message exists with the given wording (fuzzy match, unaccented both sides).
     */
    protected function assertSessionHasWarningContaining(string $needle): void
    {
        $found = false;
        $keys = ['alert-warning', 'warning', 'alert-danger', 'danger'];
        foreach ($keys as $key) {
            $message = $this->app['session']->get($key);
            $messages = is_array($message) ? $message : [$message];
            foreach ($messages as $single) {
                if ($single === null) {
                    continue;
                }
                $haystack = is_array($single) ? implode(' ', $single) : (string) $single;
                if (str_contains($this->normalize($haystack), $this->normalize($needle))) {
                    $found = true;
                    break 2;
                }
            }
        }

        $this->assertTrue($found, "Expected a warning flash containing '{$needle}'.");
    }

    protected function normalize(string $value): string
    {
        $map = ['á' => 'a', 'à' => 'a', 'ã' => 'a', 'â' => 'a', 'é' => 'e', 'ê' => 'e', 'í' => 'i', 'ó' => 'o', 'ô' => 'o', 'õ' => 'o', 'ú' => 'u', 'ç' => 'c'];

        return strtr(mb_strtolower(trim($value)), $map);
    }

    /**
     * Sets an environment variable across every source Laravel's env() reads.
     */
    protected function setEnv(string $key, $value): void
    {
        putenv($key.'='.$value);
        $_ENV[$key] = $value;
        $_SERVER[$key] = $value;
        $this->app['config']->set($key, $value);
    }
}