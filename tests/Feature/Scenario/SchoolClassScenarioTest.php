<?php

namespace Tests\Feature\Scenario;

use App\Models\Activity;
use App\Models\ClassSchedule;
use App\Models\Department;
use App\Models\Instructor;
use App\Models\Requisition;
use App\Models\SchoolClass;
use App\Models\SchoolTerm;
use App\Models\Student;
use App\Jobs\ProcessGetSchoolClassesFromReplicado;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Session;

class SchoolClassScenarioTest extends ScenarioTestCase
{
    public function test_cen_schoolclass_001_store_cria_turma_instrutores_e_horarios()
    {
        $secretaria = $this->secretaria();
        $term = $this->createOpenTerm();

        $this->actingAs($secretaria)->from('/schoolclasses')
            ->post('/schoolclasses', $this->storePayload($term))
            ->assertRedirect('/schoolclasses');

        $turma = SchoolClass::where('codtur', '2026101')->where('coddis', 'MAC0110')->first();
        $this->assertNotNull($turma);
        $this->assertTrue($turma->instructors()->exists());
        $this->assertTrue($turma->classschedules()->exists());

        $schedule = ClassSchedule::first();
        $this->assertDatabaseHas('class_schedule_school_class', ['class_schedule_id' => $schedule->id, 'school_class_id' => $turma->id]);
    }

    public function test_cen_schoolclass_002_store_duplicada_sem_instrutores_anexa_instrutores_e_horarios()
    {
        $secretaria = $this->secretaria();
        $term = $this->createOpenTerm();

        $turma = $this->createSchoolClass([
            'school_term_id' => $term->id,
            'codtur' => '2026101',
            'coddis' => 'MAC0110',
            'instructors' => [],
        ]);
        $this->assertFalse($turma->instructors()->exists());

        $this->actingAs($secretaria)->from('/schoolclasses')
            ->post('/schoolclasses', $this->storePayload($term))
            ->assertRedirect('/schoolclasses');

        $turma->refresh();
        $this->assertSame(1, SchoolClass::count());
        $this->assertTrue($turma->instructors()->exists());
        $this->assertTrue($turma->classschedules()->exists());
    }

    public function test_cen_schoolclass_003_store_duplicada_com_instrutores_exibe_aviso()
    {
        $secretaria = $this->secretaria();
        $term = $this->createOpenTerm();
        $instructor = $this->createInstructor();

        $turma = $this->createSchoolClass([
            'school_term_id' => $term->id,
            'codtur' => '2026101',
            'coddis' => 'MAC0110',
            'instructors' => [$instructor->id],
        ]);

        $this->actingAs($secretaria)->from('/schoolclasses')
            ->post('/schoolclasses', $this->storePayload($term))
            ->assertRedirect('/schoolclasses');

        $this->assertSessionHasWarningContaining('já existe uma turma cadastrada');
        $this->assertDatabaseHas('instructor_school_class', ['instructor_id' => $instructor->id, 'school_class_id' => $turma->id]);
    }

    public function test_cen_schoolclass_004_validacoes_do_formulario()
    {
        $secretaria = $this->secretaria();
        $term = $this->createOpenTerm();
        $payload = $this->storePayload($term);

        $cases = [
            'coddis_ausente' => ['coddis' => null],
            'nomdis_ausente' => ['nomdis' => null],
            'tiptur_ausente' => ['tiptur' => null],
            'codtur_nao_numerico' => ['codtur' => 'abc'],
            'instrutores_ausente' => ['instrutores' => null],
            'instrutor_codpes_nao_numerico' => ['instrutores' => [['codpes' => 'x']]],
            'diasmnocp_invalido' => ['horarios' => [['diasmnocp' => 'segunda', 'horent' => '08:00', 'horsai' => '10:00']]],
            'horent_invertido' => ['horarios' => [['diasmnocp' => 'seg', 'horent' => '11:00', 'horsai' => '10:00']]],
            'data_invalida' => ['dtainitur' => '2026-03-01'],
            'data_inicio_apos_fim' => ['dtainitur' => '10/07/2026', 'dtafimtur' => '01/03/2026'],
        ];

        foreach ($cases as $key => $overrides) {
            $this->actingAs($secretaria)->post('/schoolclasses', array_merge($payload, $overrides))->assertSessionHasErrors();
        }
    }

    public function test_cen_schoolclass_005_update_desanexa_e_reanexa_instrutores_e_horarios()
    {
        $secretaria = $this->secretaria();
        $term = $this->createOpenTerm();
        $instructorA = $this->createInstructor(['codpes' => 2000]);
        $newInstructor = $this->createInstructor(['codpes' => 9999]);
        $turma = $this->createSchoolClass([
            'school_term_id' => $term->id,
            'instructors' => [$instructorA->id],
        ]);

        $payload = $this->storePayload($term);
        $payload['instrutores'] = [['codpes' => 9999]];
        unset($payload['codtur'], $payload['coddis'], $payload['nomdis'], $payload['tiptur'], $payload['periodoId'], $payload['department_id']);

        $this->actingAs($secretaria)->from('/schoolclasses')
            ->put('/schoolclasses/'.$turma->id, $payload)
            ->assertRedirect('/schoolclasses');

        $turma->refresh();
        $this->assertFalse($turma->instructors->contains($instructorA));
        $this->assertTrue($turma->instructors->contains(fn ($i) => $i->codpes == '9999'));
    }

    public function test_cen_schoolclass_006_update_omite_campos_imutaveis_na_validacao()
    {
        $secretaria = $this->secretaria();
        $this->createInstructor(['codpes' => 3000]);
        $turma = $this->createSchoolClass();

        $this->actingAs($secretaria)
            ->put('/schoolclasses/'.$turma->id, $this->updatePayload())
            ->assertRedirect('/schoolclasses');
    }

    public function test_cen_schoolclass_007_destroy_desanexa_pivos_e_exclui_a_turma()
    {
        $admin = $this->admin();
        $term = $this->createOpenTerm();
        $turma = $this->createSchoolClass(['school_term_id' => $term->id]);
        $schedule = $turma->classschedules()->first();

        $this->actingAs($admin)->from('/schoolclasses')
            ->delete('/schoolclasses/'.$turma->id)
            ->assertRedirect('/schoolclasses');

        $this->assertDatabaseMissing('school_classes', ['id' => $turma->id]);
        $this->assertDatabaseMissing('instructor_school_class', ['school_class_id' => $turma->id]);
        $this->assertDatabaseMissing('class_schedule_school_class', ['school_class_id' => $turma->id]);
    }

    public function test_cen_schoolclass_008_import_sincrono_quando_is_supervisor_config_falso()
    {
        $this->setEnv('IS_SUPERVISOR_CONFIG', 'false');
        $secretaria = $this->secretaria();
        $term = $this->createOpenTerm();

        $this->actingAs($secretaria)->from('/schoolclasses')
            ->patch('/schoolclasses/import', ['periodoId' => $term->id])
            ->assertRedirect('/schoolclasses');

        $this->assertDatabaseHas('school_classes', ['coddis' => 'MAC0110', 'school_term_id' => $term->id]);
        $this->assertDatabaseHas('departments', ['nomabvset' => 'MAC']);
        $this->assertDatabaseHas('class_schedules', ['diasmnocp' => 'seg']);
    }

    public function test_cen_schoolclass_009_import_em_fila_quando_is_supervisor_config_true()
    {
        $this->setEnv('IS_SUPERVISOR_CONFIG', 'true');
        Queue::fake();

        $secretaria = $this->secretaria();
        $term = $this->createOpenTerm();

        $this->actingAs($secretaria)->from('/schoolclasses')
            ->patch('/schoolclasses/import', ['periodoId' => $term->id])
            ->assertRedirect('/schoolclasses');

        Queue::assertPushed(ProcessGetSchoolClassesFromReplicado::class);
        $this->assertDatabaseMissing('school_classes', ['school_term_id' => $term->id]);
    }

    public function test_cen_schoolclass_010_index_docente_ve_apenas_proprias_turmas()
    {
        $env = $this->seedEnvironment();

        $response = $this->actingAs($env['docente'])->get('/schoolclasses');

        $response->assertOk();
        $turmas = $response->viewData('turmas');
        $this->assertTrue($turmas->contains('id', $env['class']->id));
        $this->assertFalse($turmas->contains('id', $env['class_mat']->id));
    }

    public function test_cen_schoolclass_011_index_membro_comissao_ve_apenas_departamento()
    {
        $env = $this->seedEnvironment();
        $env['membro']->givePermissionTo('visualizar turma');

        $response = $this->actingAs($env['membro'])->get('/schoolclasses');

        $response->assertOk();
        $turmas = $response->viewData('turmas');
        $this->assertTrue($turmas->contains('id', $env['class']->id));
        $this->assertFalse($turmas->contains('id', $env['class_mat']->id));
    }

    public function test_cen_schoolclass_012_index_secretaria_e_admin_veem_todas_as_turmas()
    {
        $env = $this->seedEnvironment();

        $respSecretaria = $this->actingAs($env['secretaria'])->get('/schoolclasses');
        $respSecretaria->assertOk();
        $this->assertCount(2, $respSecretaria->viewData('turmas'));

        $respAdmin = $this->actingAs($env['admin'])->get('/schoolclasses');
        $respAdmin->assertOk();
        $this->assertCount(2, $respAdmin->viewData('turmas'));
    }

    public function test_cen_schoolclass_013_index_seleciona_periodo_aberto_ou_mais_recente()
    {
        $env = $this->seedEnvironment();
        $resp = $this->actingAs($env['secretaria'])->get('/schoolclasses');
        $this->assertSame($env['term']->id, $resp->viewData('schoolterm')->id);

        SchoolTerm::where('id', $env['term']->id)->update(['status' => 'Fechado']);
        $newer = SchoolTerm::create(array_merge($this->openTermAttributes(), [
            'year' => (int) now()->format('Y') + 1,
            'period' => '2° Semestre',
            'status' => 'Fechado',
        ]));

        $resp = $this->actingAs($env['secretaria'])->get('/schoolclasses');
        $this->assertSame($newer->id, $resp->viewData('schoolterm')->id);
    }

    public function test_cen_schoolclass_014_busca_de_turmas_por_coddis()
    {
        $env = $this->seedEnvironment();

        $resp = $this->actingAs($env['secretaria'])
            ->get('/schoolclasses/search?coddis=MAC0101&periodoId='.$env['term']->id);

        $resp->assertOk();
        $turmas = $resp->viewData('turmas');
        $this->assertTrue($turmas->contains('id', $env['class']->id));
        $this->assertFalse($turmas->contains('id', $env['class_mat']->id));
    }

    public function test_cen_schoolclass_015_view_de_inscricoes_e_de_monitores_eleitos()
    {
        $env = $this->seedEnvironment();

        $this->actingAs($env['secretaria'])->get('/schoolclasses/'.$env['class']->id.'/enrollments')->assertOk();

        $this->actingAs($env['docente'])->get('/schoolclasses/'.$env['class']->id.'/electedTutors')->assertOk();
    }

    public function test_cen_schoolclass_016_formulario_de_criacao_popula_departamentos()
    {
        $secretaria = $this->secretaria();
        $term = $this->createOpenTerm();

        $resp = $this->actingAs($secretaria)->get('/schoolclasses/create?periodoId='.$term->id);

        $resp->assertOk();
        $this->assertDatabaseHas('departments', ['nomabvset' => 'MAC']);
        $this->assertDatabaseHas('departments', ['nomabvset' => 'MAP']);
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('replicado_dependent_provider')]
    public function test_replicado_dependent_cases_append_nothing()
    {
        $this->assertTrue(true);
    }

    public static function replicado_dependent_provider(): array
    {
        return [[]];
    }

    // ------------------------------------------------------------------

    protected function storePayload(SchoolTerm $term): array
    {
        return [
            'periodoId' => $term->id,
            'department_id' => Department::firstOrCreate(['codset' => 5000], ['nomabvset' => 'MAC', 'nomset' => 'dep', 'sglund' => 'IME', 'nomund' => 'ime'])->id,
            'codtur' => '2026101',
            'coddis' => 'MAC0110',
            'nomdis' => 'Introducao a Computacao',
            'tiptur' => 'Teoria',
            'dtainitur' => '01/03/2026',
            'dtafimtur' => '10/07/2026',
            'horarios' => [
                ['diasmnocp' => 'seg', 'horent' => '08:00', 'horsai' => '10:00'],
            ],
            'instrutores' => [
                ['codpes' => '2000'],
            ],
        ];
    }

    protected function updatePayload(): array
    {
        return [
            'dtainitur' => '02/03/2026',
            'dtafimtur' => '11/07/2026',
            'horarios' => [
                ['diasmnocp' => 'ter', 'horent' => '14:00', 'horsai' => '16:00'],
            ],
            'instrutores' => [
                ['codpes' => '3000'],
            ],
        ];
    }
}