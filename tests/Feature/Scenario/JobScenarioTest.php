<?php

namespace Tests\Feature\Scenario;

use App\Jobs\ProcessGetSchoolClassesFromReplicado;
use App\Jobs\ProcessImportOldDB;
use App\Models\SchoolClass;
use romanzipp\QueueMonitor\Models\Monitor;

class JobScenarioTest extends ScenarioTestCase
{
    public function test_cen_job_001_process_school_classes_sincroniza_turmas()
    {
        $term = $this->createOpenTerm();

        $job = new ProcessGetSchoolClassesFromReplicado($term);
        $job->handle();

        // o stub do Replicado retorna uma turma MAC0110 ligada ao termo
        $class = SchoolClass::where('school_term_id', $term->id)->where('coddis', 'MAC0110')->first();
        $this->assertNotNull($class);
        $this->assertGreaterThan(0, $class->instructors()->count());
        $this->assertGreaterThan(0, $class->classschedules()->count());
    }

    public function test_cen_job_002_configuracao_do_job_de_turmas()
    {
        $this->assertSame(3600, (new ProcessGetSchoolClassesFromReplicado($this->createOpenTerm()))->timeout);
        $this->assertContains(\romanzipp\QueueMonitor\Traits\IsMonitored::class, class_uses(ProcessGetSchoolClassesFromReplicado::class));
    }

    public function test_cen_job_003_process_import_old_db_eh_monitorado_com_timeout_alto()
    {
        $this->assertSame(9999, (new ProcessImportOldDB('conteudo', '1'))->timeout);
        $this->assertContains(\romanzipp\QueueMonitor\Traits\IsMonitored::class, class_uses(ProcessImportOldDB::class));
    }

    public function test_cen_job_004_progresso_consultavel_pelo_monitor_controller()
    {
        $admin = $this->admin();

        // cria registros de monitor do job de turmas com progresso diferente
        Monitor::query()->create(['name' => 'App\Jobs\ProcessGetSchoolClassesFromReplicado', 'job_id' => 1, 'progress' => 40]);
        Monitor::query()->create(['name' => 'App\Jobs\ProcessGetSchoolClassesFromReplicado', 'job_id' => 2, 'progress' => 90]);

        $resp = $this->actingAs($admin)->get('/monitor/getimportschoolclassesjob');
        $resp->assertOk();
        $this->assertSame(90, (int) json_decode($resp->getContent(), true)['progress']);
    }
}
