<?php

namespace Tests\Feature\Scenario;

use App\Models\InstructorEvaluation;
use App\Models\SchoolTerm;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\URL;

class InstructorEvaluationScenarioTest extends ScenarioTestCase
{
    protected function envWithSelection(bool $evaluationWindowActive = true): array
    {
        $env = $this->seedEnvironment();

        if (! $evaluationWindowActive) {
            $env['term']->update([
                'start_date_evaluations' => now()->subDays(30)->format('d/m/Y'),
                'end_date_evaluations' => now()->subDays(20)->format('d/m/Y'),
            ]);
        }

        $selection = $this->createSelection(['enrollment_id' => $env['enrollment']->id]);

        return ['env' => $env, 'selection' => $selection];
    }

    private function signedCreateUrl($selection): string
    {
        return URL::signedRoute('instructorevaluations.create', ['selectionID' => $selection->id]);
    }

    private function payload($selection, ?string $hash = null, ?string $comments = null): array
    {
        return [
            'selection_id' => $selection->id,
            'selection_hash' => $hash ?? Hash::make(json_encode($selection->fresh()->toArray())),
            'ease_of_contact' => 1,
            'efficiency' => 1,
            'reliability' => 1,
            'overall' => 1,
            'comments' => $comments,
        ];
    }

    public function test_cen_inseval_001_index_exige_permissao_visualizar_avaliacoes_dos_docentes()
    {
        $this->createOpenTerm();
        $docente = $this->docente();

        $this->actingAs($docente)->get('/instructorevaluations')->assertForbidden();
    }

    public function test_cen_inseval_002_instructor_index_lista_selecoes_do_docente_autenticado()
    {
        $env = $this->seedEnvironment();
        $mySelection = $this->createSelection(['enrollment_id' => $env['enrollment']->id]);

        $outroInstructor = $this->createInstructor(['nompes' => 'Outro Professor']);
        $outroDocente = $this->docente(['codpes' => $outroInstructor->codpes], $outroInstructor);
        $outroClass = $this->createSchoolClass(['school_term_id' => $env['term']->id, 'coddis' => 'MAC0202', 'instructors' => [$outroInstructor->id]]);
        $this->createRequisition(['instructor_id' => $outroInstructor->id, 'school_class_id' => $outroClass->id]);
        $outroStudent = $this->createStudent(['nompes' => 'Outro Aluno']);
        $outroEnrollment = $this->createEnrollment(['student_id' => $outroStudent->id, 'school_class_id' => $outroClass->id]);
        $outroSelection = $this->createSelection(['enrollment_id' => $outroEnrollment->id]);

        $resp = $this->actingAs($env['docente'])->get('/instructors/evaluations');
        $resp->assertOk();

        $ids = $resp->viewData('selections')->pluck('id')->all();
        $this->assertContains($mySelection->id, $ids);
        $this->assertNotContains($outroSelection->id, $ids);
    }

    public function test_cen_inseval_003_create_por_url_assinada_ou_docente_instrutor()
    {
        $fixture = $this->envWithSelection();

        // docente instrutor autenticado
        $this->actingAs($fixture['env']['docente'])
            ->get('/instructorevaluations/create?selectionID='.$fixture['selection']->id)
            ->assertOk();

        // url assinada sem autenticação
        $this->get($this->signedCreateUrl($fixture['selection']))->assertOk();
    }

    public function test_cen_inseval_004_create_fora_da_janela_de_avaliacao_bloqueia()
    {
        $fixture = $this->envWithSelection(false);

        $this->from('/')->get($this->signedCreateUrl($fixture['selection']))->assertRedirect('/');
        $this->assertSessionHasWarningContaining('avaliação encerrado');
    }

    public function test_cen_inseval_005_create_por_docente_nao_instrutor_e_negado()
    {
        $fixture = $this->envWithSelection();

        $outroInstructor = $this->createInstructor(['nompes' => 'Outro Professor']);
        $outroDocente = $this->docente(['codpes' => $outroInstructor->codpes], $outroInstructor);

        $this->from('/')->actingAs($outroDocente)
            ->get('/instructorevaluations/create?selectionID='.$fixture['selection']->id)
            ->assertRedirect('/');
        $this->assertSessionHasWarningContaining('responsabilidade');
    }

    public function test_cen_inseval_006_store_persiste_avaliacao_via_update_or_create()
    {
        $fixture = $this->envWithSelection();

        $this->actingAs($fixture['env']['docente'])->from('/instructors/evaluations')
            ->post('/instructorevaluations', $this->payload($fixture['selection']))
            ->assertRedirect(route('instructorevaluations.instructorIndex'));

        $this->assertSame(1, InstructorEvaluation::where('selection_id', $fixture['selection']->id)->count());

        // novo envio atualiza o mesmo registro
        $this->actingAs($fixture['env']['docente'])->from('/instructors/evaluations')
            ->post('/instructorevaluations', $this->payload($fixture['selection'], null, 'comentario novo'))
            ->assertRedirect(route('instructorevaluations.instructorIndex'));

        $this->assertSame(1, InstructorEvaluation::where('selection_id', $fixture['selection']->id)->count());
        $this->assertSame('comentario novo', InstructorEvaluation::where('selection_id', $fixture['selection']->id)->first()->comments);
    }

    public function test_cen_inseval_007_store_por_link_assinado_valida_hash()
    {
        $fixture = $this->envWithSelection();
        $selection = $fixture['selection'];
        $validHash = Hash::make(json_encode($selection->fresh()->toArray()));

        // hash válido
        $this->post('/instructorevaluations', $this->payload($selection, $validHash))->assertRedirect('/');
        $this->assertSame(1, InstructorEvaluation::where('selection_id', $selection->id)->count());

        // hash inválido
        $this->post('/instructorevaluations', $this->payload($selection, 'hash-invalido'))->assertRedirect('/');
        $this->assertSame(1, InstructorEvaluation::where('selection_id', $selection->id)->count());
        $this->assertSessionHasWarningContaining('não pertence');
    }

    public function test_cen_inseval_008_validacao_do_store()
    {
        $fixture = $this->envWithSelection();
        $selection = $fixture['selection'];

        $cases = [
            'ease_invalido' => ['ease_of_contact' => 5],
            'efficiency_invalido' => ['efficiency' => -1],
            'reliability_invalido' => ['reliability' => 'x'],
            'overall_invalido' => ['overall' => 3],
            'comments_excede_65536' => ['comments' => str_repeat('a', 66000)],
        ];

        foreach ($cases as $key => $overrides) {
            $this->actingAs($fixture['env']['docente'])
                ->post('/instructorevaluations', array_merge($this->payload($selection), $overrides))
                ->assertSessionHasErrors();
        }
    }

    public function test_cen_inseval_009_show_restringe_ao_instrutor_dono_ou_permissao()
    {
        $fixture = $this->envWithSelection();
        $ie = InstructorEvaluation::create([
            'selection_id' => $fixture['selection']->id,
            'ease_of_contact' => 1,
            'efficiency' => 1,
            'reliability' => 1,
            'overall' => 1,
        ]);

        // instrutor dono
        $this->actingAs($fixture['env']['docente'])->get('/instructorevaluations/'.$ie->id)->assertOk();
        // quem tem a permissão
        $this->actingAs($fixture['env']['secretaria'])->get('/instructorevaluations/'.$ie->id)->assertOk();

        // terceiro sem permissão
        $semPermissao = $this->createUser('Monitor');
        $this->actingAs($semPermissao)->get('/instructorevaluations/'.$ie->id)->assertForbidden();
    }

    public function test_cen_inseval_010_edit_update_restrito_ao_instrutor_dono()
    {
        $fixture = $this->envWithSelection();
        $ie = InstructorEvaluation::create([
            'selection_id' => $fixture['selection']->id,
            'ease_of_contact' => 1,
            'efficiency' => 1,
            'reliability' => 1,
            'overall' => 1,
        ]);

        // não-instrutor não pode editar
        $outroInstructor = $this->createInstructor(['nompes' => 'Outro Professor']);
        $outroDocente = $this->docente(['codpes' => $outroInstructor->codpes], $outroInstructor);
        $this->from('/')->actingAs($outroDocente)->get('/instructorevaluations/'.$ie->id.'/edit')->assertRedirect('/');
        $this->assertSessionHasWarningContaining('responsabilidade');

        // instrutor dono atualiza
        $this->actingAs($fixture['env']['docente'])->from('/instructors/evaluations')
            ->put('/instructorevaluations/'.$ie->id, [
                'ease_of_contact' => 0,
                'efficiency' => 2,
                'reliability' => 1,
                'overall' => 0,
            ])
            ->assertRedirect(route('instructorevaluations.instructorIndex'));

        $this->assertSame(0, (int) $ie->fresh()->ease_of_contact);
    }
}
