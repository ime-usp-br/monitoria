<?php

namespace Tests\Feature\Security;

use Tests\TestCase;

class AuthRoutesTest extends TestCase
{
    public function test_rotas_protegidas_redirecionam_anonimo_para_login(): void
    {
        foreach (['/users', '/schoolclasses', '/tutors', '/reports', '/certificates', '/olddb'] as $path) {
            $this->get($path)->assertRedirect('/login');
        }
    }

    public function test_home_e_publica(): void
    {
        $this->get('/')->assertOk();
    }

    public function test_endpoint_json_protegido_retorna_401(): void
    {
        $this->getJson('/students?codpes=123')->assertStatus(401);
        $this->getJson('/instructors?codpes=123')->assertStatus(401);
    }
}
