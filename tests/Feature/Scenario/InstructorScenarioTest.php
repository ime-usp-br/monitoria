<?php

namespace Tests\Feature\Scenario;

use App\Models\Instructor;
use App\Models\Requisition;
use Tests\TestHelpers\ReplicadoStubs;

class InstructorScenarioTest extends ScenarioTestCase
{
    public function test_cen_instructor_001_busca_json_por_codpes_retorna_nome_do_docente()
    {
        $resp = $this->getJson('/instructors?codpes=2000');

        $resp->assertOk()->assertExactJson(['Professor Do Ime']);
    }

    public function test_cen_instructor_002_busca_json_por_codpes_sem_vinculo_docente_retorna_vazio()
    {
        ReplicadoStubs::registerPessoa('vinculos', ['Aluno']);

        $resp = $this->getJson('/instructors?codpes=2000');

        $resp->assertOk()->assertExactJson(['']);
    }

    public function test_cen_instructor_003_index_lista_docentes_ordenados_pela_quantidade_de_solicitacoes()
    {
        $secretaria = $this->secretaria();

        $a = $this->createInstructor(['nompes' => 'Docente A']);
        $b = $this->createInstructor(['nompes' => 'Docente B']);
        $c = $this->createInstructor(['nompes' => 'Docente C']);

        $classA = $this->createSchoolClass(['instructors' => [$a->id]]);
        $classB = $this->createSchoolClass(['instructors' => [$b->id]]);

        Requisition::create(['instructor_id' => $a->id, 'school_class_id' => $classA->id, 'requested_number' => 3, 'priority' => 1]);
        Requisition::create(['instructor_id' => $a->id, 'school_class_id' => $this->createSchoolClass(['instructors' => [$a->id]])->id, 'requested_number' => 2, 'priority' => 1]);
        Requisition::create(['instructor_id' => $b->id, 'school_class_id' => $classB->id, 'requested_number' => 1, 'priority' => 1]);

        $resp = $this->actingAs($secretaria)->get('/instructors');
        $resp->assertOk();

        $docentes = $resp->viewData('docentes');
        $ids = collect($docentes)->map(fn ($d) => $d->codpes)->values()->all();

        $this->assertSame($a->codpes, $ids[0]);
        $this->assertSame($b->codpes, $ids[1]);
        $this->assertContains($c->codpes, $ids);
    }

    public function test_cen_instructor_004_view_de_solicitacoes_do_docente()
    {
        $secretaria = $this->secretaria();
        $instructor = $this->createInstructor();

        $resp = $this->actingAs($secretaria)->get('/instructors/'.$instructor->id.'/requisitions');

        $resp->assertOk();
        $this->assertSame($instructor->id, $resp->viewData('docente')->id);
    }

    public function test_cen_instructor_005_busca_por_codpes()
    {
        $secretaria = $this->secretaria();
        $a = $this->createInstructor(['codpes' => 1111]);
        $b = $this->createInstructor(['codpes' => 2222]);

        $resp = $this->actingAs($secretaria)->get('/instructors/search?codpes=1111');

        $resp->assertOk();
        $docentes = $resp->viewData('docentes');
        $this->assertTrue($docentes->contains('id', $a->id));
        $this->assertFalse($docentes->contains('id', $b->id));
    }

    public function test_cen_instructor_006_metodos_crud_de_docente_sao_stubs_vazios()
    {
        $secretaria = $this->secretaria();
        $instructor = $this->createInstructor();

        $this->actingAs($secretaria)->get('/instructors/create')->assertStatus(200);
        // store/update negados pelos Form Requests cujo authorize() retorna false
        $this->actingAs($secretaria)->post('/instructors', ['codpes' => 1])->assertForbidden();
        $this->actingAs($secretaria)->get('/instructors/'.$instructor->id)->assertStatus(200);
        $this->actingAs($secretaria)->get('/instructors/'.$instructor->id.'/edit')->assertStatus(200);
        $this->actingAs($secretaria)->put('/instructors/'.$instructor->id, ['codpes' => 2])->assertForbidden();
        $this->actingAs($secretaria)->delete('/instructors/'.$instructor->id)->assertStatus(200);
        $this->assertDatabaseHas('instructors', ['id' => $instructor->id]);
    }
}