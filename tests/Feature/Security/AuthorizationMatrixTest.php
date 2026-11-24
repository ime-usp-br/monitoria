<?php

namespace Tests\Feature\Security;

use App\Models\InstructorEvaluation;
use App\Models\SelfEvaluation;
use Illuminate\Http\UploadedFile;
use Tests\Feature\Scenario\ScenarioTestCase;

class AuthorizationMatrixTest extends ScenarioTestCase
{
    public function test_enrollment_update_and_delete_are_restricted_to_owner(): void
    {
        $env = $this->seedEnvironment();
        $other = $this->aluno();

        $this->actingAs($other)
            ->put('/enrollments/'.$env['enrollment']->id, ['preferencia_horario' => 'Noite'])
            ->assertForbidden();

        $this->actingAs($other)
            ->delete('/enrollments/'.$env['enrollment']->id)
            ->assertForbidden();

        $this->assertSame('Manhã', $env['enrollment']->fresh()->preferencia_horario);
        $this->assertDatabaseHas('enrollments', ['id' => $env['enrollment']->id]);

        $this->actingAs($env['aluno'])
            ->put('/enrollments/'.$env['enrollment']->id, ['preferencia_horario' => 'Noite'])
            ->assertRedirect('/enrollments');

        $this->assertSame('Noite', $env['enrollment']->fresh()->preferencia_horario);
    }

    public function test_self_evaluation_update_is_restricted_to_owner(): void
    {
        $env = $this->seedEnvironment();
        $selection = $this->createSelection(['enrollment_id' => $env['enrollment']->id]);

        $evaluation = SelfEvaluation::create([
            'selection_id' => $selection->id,
            'student_amount' => 1,
            'homework_amount' => 1,
            'workload' => 1,
        ]);
        $other = $this->aluno();

        $this->actingAs($other)
            ->put('/selfevaluations/'.$evaluation->id, [
                'student_amount' => 9,
                'homework_amount' => 9,
                'workload' => 2,
            ])
            ->assertForbidden();

        $this->assertSame(1, (int) $evaluation->fresh()->student_amount);

        $this->actingAs($env['aluno'])
            ->put('/selfevaluations/'.$evaluation->id, [
                'student_amount' => 3,
                'homework_amount' => 4,
                'workload' => 2,
            ])
            ->assertRedirect(route('selfevaluations.studentIndex'));

        $this->assertSame(3, (int) $evaluation->fresh()->student_amount);
    }

    public function test_instructor_evaluation_update_is_restricted_to_owner_instructor(): void
    {
        $env = $this->seedEnvironment();
        $selection = $this->createSelection(['enrollment_id' => $env['enrollment']->id]);
        $evaluation = InstructorEvaluation::create([
            'selection_id' => $selection->id,
            'ease_of_contact' => 0,
            'efficiency' => 0,
            'reliability' => 0,
            'overall' => 0,
        ]);
        $other = $this->docente();

        $this->actingAs($other)
            ->put('/instructorevaluations/'.$evaluation->id, [
                'ease_of_contact' => 2,
                'efficiency' => 2,
                'reliability' => 2,
                'overall' => 2,
            ])
            ->assertForbidden();

        $this->assertSame(0, (int) $evaluation->fresh()->overall);

        $this->actingAs($env['docente'])
            ->put('/instructorevaluations/'.$evaluation->id, [
                'ease_of_contact' => 1,
                'efficiency' => 1,
                'reliability' => 1,
                'overall' => 1,
            ])
            ->assertRedirect(route('instructorevaluations.instructorIndex'));

        $this->assertSame(1, (int) $evaluation->fresh()->overall);
    }

    public function test_school_record_update_is_restricted_to_owner_student(): void
    {
        $env = $this->seedEnvironment();
        $other = $this->aluno();
        $file = UploadedFile::fake()->create('historico.pdf', 100, 'application/pdf');

        $this->actingAs($other)
            ->put('/schoolRecords/'.$env['school_record']->id, ['file' => $file])
            ->assertForbidden();

        $this->assertSame('20261/historico.pdf', $env['school_record']->fresh()->file_path);

        $this->actingAs($env['aluno'])
            ->put('/schoolRecords/'.$env['school_record']->id, [
                'file' => UploadedFile::fake()->create('novo.pdf', 100, 'application/pdf'),
            ])
            ->assertRedirect(route('enrollments.index'));

        $this->assertNotSame('20261/historico.pdf', $env['school_record']->fresh()->file_path);
    }

    public function test_requisition_update_is_restricted_to_owning_instructor(): void
    {
        $env = $this->seedEnvironment();
        $other = $this->docente();

        $this->actingAs($other)
            ->put('/requisitions/'.$env['requisition']->id, $this->requisitionPayload())
            ->assertForbidden();

        $this->assertSame(1, (int) $env['requisition']->fresh()->requested_number);

        $this->actingAs($env['docente'])
            ->put('/requisitions/'.$env['requisition']->id, $this->requisitionPayload(2))
            ->assertRedirect('/requisitions');

        $this->assertSame(2, (int) $env['requisition']->fresh()->requested_number);
    }

    public function test_school_class_update_is_restricted_to_owning_instructor(): void
    {
        $env = $this->seedEnvironment();
        $other = $this->docente();

        $this->actingAs($other)
            ->put('/schoolclasses/'.$env['class']->id, $this->schoolClassPayload($env['instructor']->codpes))
            ->assertForbidden();

        $this->actingAs($env['docente'])
            ->put('/schoolclasses/'.$env['class']->id, $this->schoolClassPayload($env['instructor']->codpes))
            ->assertRedirect('/schoolclasses');
    }

    public function test_class_enrollments_are_not_visible_to_students_or_unrelated_teachers(): void
    {
        $env = $this->seedEnvironment();

        $this->actingAs($env['aluno'])
            ->get('/schoolclasses/'.$env['class']->id.'/enrollments')
            ->assertForbidden();

        $this->actingAs($this->docente())
            ->get('/schoolclasses/'.$env['class']->id.'/enrollments')
            ->assertForbidden();

        $this->actingAs($env['docente'])
            ->get('/schoolclasses/'.$env['class']->id.'/enrollments')
            ->assertOk();

        $this->actingAs($env['secretaria'])
            ->get('/schoolclasses/'.$env['class']->id.'/enrollments')
            ->assertOk();
    }

    public function test_school_record_download_requires_record_id_and_permission(): void
    {
        $env = $this->seedEnvironment();
        \Illuminate\Support\Facades\Storage::put($env["school_record"]->file_path, "pdf");

        $this->actingAs($env["secretaria"])
            ->post("/schoolrecords/download", ["path" => $env["school_record"]->file_path])
            ->assertSessionHasErrors("schoolrecord_id");

        $this->actingAs($env["secretaria"])
            ->post("/schoolrecords/download", ["schoolrecord_id" => $env["school_record"]->id])
            ->assertOk();

        $this->actingAs($env["aluno"])
            ->post("/schoolrecords/download", ["schoolrecord_id" => $env["school_record"]->id])
            ->assertForbidden();
    }

    private function requisitionPayload(int $requestedNumber = 1): array
    {
        return [
            'requested_number' => $requestedNumber,
            'priority' => 1,
            'activities' => ['Atendimento a alunos'],
            'recommendations' => [],
            'scholarships' => [],
            'comments' => 'Teste',
        ];
    }

    private function schoolClassPayload(string $instructorCodpes): array
    {
        return [
            'dtainitur' => '01/03/2026',
            'dtafimtur' => '31/12/2026',
            'instrutores' => [['codpes' => $instructorCodpes]],
            'horarios' => [['diasmnocp' => 'seg', 'horent' => '08:00', 'horsai' => '10:00']],
        ];
    }
}
