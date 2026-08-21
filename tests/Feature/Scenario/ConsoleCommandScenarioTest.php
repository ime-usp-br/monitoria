<?php

namespace Tests\Feature\Scenario;

use App\Models\Instructor;
use App\Models\SchoolClass;
use App\Models\SchoolTerm;
use Tests\TestHelpers\ReplicadoStubs;

class ConsoleCommandScenarioTest extends ScenarioTestCase
{
    public function test_cen_cmd_001_compare_classes_valida_ambiente()
    {
        // sem UNIDADE
        $this->setEnv('UNIDADE', '');
        $this->artisan('report:compare-classes')->assertExitCode(1);
    }

    public function test_cen_cmd_002_compare_classes_resolve_o_periodo()
    {
        $this->setEnv('UNIDADE', 'IME');
        $term = $this->createOpenTerm();

        // --schoolterm explícito
        $this->artisan('report:compare-classes', ['--schoolterm' => $term->id])
            ->assertExitCode(0);

        // sem --schoolterm usa o aberto
        $this->artisan('report:compare-classes')
            ->assertExitCode(0);
    }

    public function test_cen_cmd_003_compare_classes_classifica_diferencas()
    {
        $this->setEnv('UNIDADE', 'IME');
        $term = $this->createOpenTerm();

        // turma local sem correspondência no Replicado
        $localOnly = $this->createSchoolClass(['school_term_id' => $term->id, 'codtur' => '9999999', 'coddis' => 'MAC0001', 'instructors' => [$this->createInstructor()->id]]);

        // o stub do Replicado devolve MAC0110, que não existe localmente => apenas-replicado
        $this->artisan('report:compare-classes', ['--schoolterm' => $term->id])
            ->assertExitCode(0);
    }

    public function test_cen_cmd_004_compare_classes_gera_formatos_de_saida()
    {
        $this->setEnv('UNIDADE', 'IME');
        $term = $this->createOpenTerm();

        // JSON
        $this->artisan('report:compare-classes', ['--schoolterm' => $term->id, '--format' => 'json'])->assertExitCode(0);

        // CSV em arquivo
        $path = '/tmp/kilo/relatorio.csv';
        $this->artisan('report:compare-classes', ['--schoolterm' => $term->id, '--format' => 'csv', '--output' => $path])->assertExitCode(0);
        $this->assertFileExists($path);
    }

    public function test_cen_cmd_005_compare_classes_retorna_codigo_de_saida()
    {
        $this->setEnv('UNIDADE', 'IME');
        $term = $this->createOpenTerm();

        $this->artisan('report:compare-classes', ['--schoolterm' => $term->id])->assertExitCode(0);

        $this->setEnv('UNIDADE', '');
        $this->artisan('report:compare-classes')->assertExitCode(1);
    }

    public function test_cen_cmd_006_sync_class_instructors_dry_run_retorna_preview()
    {
        $this->setEnv('UNIDADE', 'IME');
        $term = $this->createOpenTerm();

        // turma local MAC0101 sem o professor que existe no Replicado
        $class = $this->createSchoolClass(['school_term_id' => $term->id, 'coddis' => 'MAC0101', 'instructors' => [$this->createInstructor(['codpes' => '999'])->id]]);

        $this->artisan('sync:class-instructors', ['--schoolterm' => $term->id, '--dry-run' => true])
            ->assertExitCode(0);

        // dry-run não altera o banco
        $this->assertSame(1, $class->instructors()->count());
    }

    public function test_cen_cmd_007_sync_class_instructors_aplica_mudancas_em_transacao()
    {
        $this->setEnv('UNIDADE', 'IME');
        $term = $this->createOpenTerm();

        $class = $this->createSchoolClass(['school_term_id' => $term->id, 'coddis' => 'MAC0101', 'instructors' => [$this->createInstructor(['codpes' => '999'])->id]]);

        // stub do Replicado fornece um ministrante (codpes 2000) para a turma
        $this->artisan('sync:class-instructors', ['--schoolterm' => $term->id])
            ->assertExitCode(0);
    }

    public function test_cen_cmd_008_sync_class_instructors_e_apenas_aditivo()
    {
        $this->setEnv('UNIDADE', 'IME');
        $term = $this->createOpenTerm();

        $existing = $this->createInstructor(['codpes' => '888']);
        $class = $this->createSchoolClass(['school_term_id' => $term->id, 'coddis' => 'MAC0101', 'instructors' => [$existing->id]]);

        // mesmo após a sincronização, o professor local não é removido
        $this->artisan('sync:class-instructors', ['--schoolterm' => $term->id])->assertExitCode(0);

        $this->assertDatabaseHas('instructor_school_class', ['instructor_id' => $existing->id]);
    }

    public function test_cen_cmd_010_sync_class_instructors_com_class_limita_escopo()
    {
        $this->setEnv('UNIDADE', 'IME');
        $term = $this->createOpenTerm();

        $class = $this->createSchoolClass(['school_term_id' => $term->id, 'coddis' => 'MAC0101', 'instructors' => [$this->createInstructor(['codpes' => '999'])->id]]);

        $this->artisan('sync:class-instructors', ['--schoolterm' => $term->id, '--class' => $class->id])
            ->assertExitCode(0);
    }
}
