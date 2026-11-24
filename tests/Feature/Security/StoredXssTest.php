<?php

namespace Tests\Feature\Security;

use App\Models\SelfEvaluation;
use Tests\Feature\Scenario\ScenarioTestCase;

class StoredXssTest extends ScenarioTestCase
{
    public function test_self_evaluation_fields_are_escaped_when_rendered(): void
    {
        $env = $this->seedEnvironment();
        $selection = $this->createSelection(['enrollment_id' => $env['enrollment']->id]);

        $payload = '<script>alert(1)</script><img src=x onerror=alert(2)>';

        $evaluation = SelfEvaluation::create([
            'selection_id' => $selection->id,
            'student_amount' => 1,
            'homework_amount' => 1,
            'workload' => 1,
            'secondary_activity' => $payload,
            'workload_reason' => $payload,
        ]);

        $response = $this->actingAs($env['secretaria'])
            ->get('/selfevaluations/'.$evaluation->id);

        $response->assertOk()
            ->assertDontSee('<script>alert(1)</script>', false)
            ->assertDontSee('<img src=x onerror=alert(2)>', false)
            ->assertSee('&lt;script&gt;alert(1)&lt;/script&gt;', false)
            ->assertSee('&lt;img src=x onerror=alert(2)&gt;', false);
    }

    public function test_flash_messages_are_escaped(): void
    {
        $env = $this->seedEnvironment();
        $payload = '<script>alert(1)</script>';

        $this->actingAs($env['aluno'])
            ->withSession(['alert-warning' => $payload])
            ->get('/')
            ->assertOk()
            ->assertDontSee($payload, false)
            ->assertSee('&lt;script&gt;alert(1)&lt;/script&gt;', false);
    }
}
