<?php

namespace Tests\Feature\Scenario;

use App\Models\Instructor;
use App\Models\Student;
use App\Models\User;
use Tests\TestHelpers\ReplicadoStubs;

class UserScenarioTest extends ScenarioTestCase
{
    public function test_cen_user_001_index_exige_permissao_editar_usuario()
    {
        $semPermissao = $this->createUser('Monitor');

        $this->actingAs($semPermissao)->get('/users')->assertForbidden();
    }

    public function test_cen_user_002_index_ordena_papeis_especiais_primeiro()
    {
        $admin = $this->admin();
        $this->secretaria();
        $this->membroComissao();
        $this->presidente();
        $this->vicePresidente();
        $this->createUser('Docente');
        $this->createUser('Aluno');
        $this->createUser('Monitor');

        $resp = $this->actingAs($admin)->get('/users');
        $resp->assertOk();

        $usuarios = $resp->viewData('usuarios');
        $names = $usuarios->pluck('name')->all();

        // usuários com papéis especiais devem aparecer antes dos demais
        $specials = ['Administrador', 'Secretaria', 'Membro Comissão', 'Presidente de Comissão', 'Vice Presidente de Comissão'];

        $specialNames = [];
        $otherNames = [];
        foreach ($usuarios as $u) {
            $hasSpecial = $u->roles->pluck('name')->intersect($specials)->isNotEmpty();
            if ($hasSpecial) {
                $specialNames[] = $u->name;
            } else {
                $otherNames[] = $u->name;
            }
        }

        foreach ($specialNames as $sn) {
            $this->assertContains($sn, $names);
        }
        foreach ($otherNames as $on) {
            $this->assertContains($on, $names);
        }
        // índice do primeiro não-especial é maior que o índice do último especial
        $lastSpecialIndex = 0;
        foreach ($specialNames as $sn) {
            $lastSpecialIndex = max($lastSpecialIndex, array_search($sn, $names));
        }
        $firstOtherIndex = PHP_INT_MAX;
        foreach ($otherNames as $on) {
            $firstOtherIndex = min($firstOtherIndex, array_search($on, $names));
        }
        $this->assertLessThan($firstOtherIndex, $lastSpecialIndex);
    }

    public function test_cen_user_003_update_desanexa_todos_os_papeis_e_atribui_os_selecionados()
    {
        $admin = $this->admin();
        $target = $this->createUser('Docente');
        $target->assignRole('Aluno');

        $this->actingAs($admin)->put('/users/'.$target->id, [
            'name' => 'Novo Nome',
            'email' => $target->email,
            'roles' => ['Secretaria'],
        ])->assertRedirect('/users');

        $target->refresh();
        $this->assertTrue($target->hasRole('Secretaria'));
        $this->assertFalse($target->hasRole('Docente'));
        $this->assertFalse($target->hasRole('Aluno'));
        $this->assertSame('Novo Nome', $target->name);
    }

    public function test_cen_user_004_validacao_do_user_request()
    {
        $admin = $this->admin();
        $target = $this->createUser('Docente');
        $outro = $this->createUser('Aluno');

        // email duplicado de outro usuário sem respeitar o id atual
        $this->actingAs($admin)->put('/users/'.$target->id, [
            'name' => 'Nome',
            'email' => $outro->email,
            'roles' => ['Docente'],
        ])->assertSessionHasErrors('email');

        // roles vazio
        $this->actingAs($admin)->put('/users/'.$target->id, [
            'name' => 'Nome',
            'email' => $target->email,
            'roles' => [],
        ])->assertSessionHasErrors('roles');
    }

    public function test_cen_user_005_search_filtra_por_nome_codpes_e_papeis()
    {
        $admin = $this->admin();
        $docente = $this->docente(['name' => 'Fulano Docente']);
        $aluno = $this->aluno(['name' => 'Ciclano Aluno']);
        $this->createUser('Monitor', ['name' => 'Beltrano Monitor']);

        // por nome
        $resp = $this->actingAs($admin)->get('/users/search?name=Fulano&codpes=');
        $resp->assertOk();
        $ids = $resp->viewData('usuarios')->pluck('id')->all();
        $this->assertContains($docente->id, $ids);
        $this->assertNotContains($aluno->id, $ids);

        // por codpes (belongs to docente)
        $resp = $this->actingAs($admin)->get('/users/search?name=&codpes='.$docente->codpes);
        $resp->assertOk();
        $ids = $resp->viewData('usuarios')->pluck('id')->all();
        $this->assertContains($docente->id, $ids);
        $this->assertNotContains($aluno->id, $ids);

        // por papel
        $resp = $this->actingAs($admin)->get('/users/search?name=&codpes=&roles[]=Aluno');
        $resp->assertOk();
        $ids = $resp->viewData('usuarios')->pluck('id')->all();
        $this->assertContains($aluno->id, $ids);
        $this->assertNotContains($docente->id, $ids);
    }

    public function test_cen_user_006_loginas_renderiza_a_view_auxiliar()
    {
        $admin = $this->admin();

        $resp = $this->actingAs($admin)->get('/users/loginas');
        $resp->assertOk();
    }

    public function test_cen_user_007_metodos_remanescentes_sao_stubs()
    {
        $admin = $this->admin();
        $target = $this->createUser('Aluno');

        // stubs não alteram banco nem retornam 500
        $this->actingAs($admin)->get('/users/create')->assertOk();
        $this->actingAs($admin)->get('/users/'.$target->id)->assertOk();
        $this->actingAs($admin)->delete('/users/'.$target->id);

        $this->assertDatabaseHas('users', ['id' => $target->id]);
    }

    public function test_cen_user_008_save_sincroniza_papeis_aluno_docente_pelos_vinculos()
    {
        // vincular codpes de um novo usuário com vínculo de Aluno no Replicado
        ReplicadoStubs::rule('VINCULOPESSOAUSP', [['tipvin' => 'ALUNOGR', 'dtafimvin' => null, 'tipfnc' => null]]);

        $codpes = '12345678';
        $user = User::factory()->create(['name' => 'Novo Aluno', 'email' => 'novo@ime.usp.br', 'codpes' => $codpes]);

        $this->assertTrue($user->fresh()->hasRole('Aluno'));
    }

    public function test_cen_user_009_log_as_administrator_concede_administrador_automaticamente()
    {
        $this->setEnv('LOG_AS_ADMINISTRATOR', '987654321');

        $codpes = '987654321';
        $user = User::factory()->create(['name' => 'Adm Automatico', 'email' => 'adm@ime.usp.br', 'codpes' => $codpes]);

        $this->assertTrue($user->fresh()->hasRole('Administrador'));
    }

    public function test_cen_user_010_login_cria_student_e_instructor_correspondente()
    {
        // Aluno sem registro Student
        $aluno = $this->createUser('Aluno', ['codpes' => '11111111', 'name' => 'Aluno Novo']);
        Student::where('codpes', '11111111')->delete();

        $this->actingAs($aluno)->get('/');
        $this->assertDatabaseHas('students', ['codpes' => '11111111']);

        // Docente sem registro Instructor
        $instructor = $this->createInstructor(['codpes' => '22222222']);
        Instructor::where('codpes', '22222222')->delete();
        $docente = $this->createUser('Docente', ['codpes' => '22222222']);

        $this->actingAs($docente)->get('/');
        $this->assertDatabaseHas('instructors', ['codpes' => '22222222']);
    }
}
