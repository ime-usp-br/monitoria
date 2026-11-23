<?php

namespace App\Services;

use App\Models\Department;
use App\Models\Enrollment;
use App\Models\Frequency;
use App\Models\Instructor;
use App\Models\Requisition;
use App\Models\SchoolClass;
use App\Models\SchoolTerm;
use App\Models\Selection;
use App\Models\Student;
use App\Models\User;
use Illuminate\Support\Collection;
use InvalidArgumentException;

class MailTemplateContext
{
    public function build(array $context, string $template): array
    {
        $includeSexo = str_contains($template, 'sexo');

        return array_map(
            fn ($value) => $this->value($value, $includeSexo),
            $context
        );
    }

    private function value($value, bool $includeSexo)
    {
        if ($value instanceof Student) {
            return $this->student($value, $includeSexo);
        }

        if ($value instanceof SchoolClass) {
            return $this->schoolClass($value, $includeSexo);
        }

        if ($value instanceof SchoolTerm) {
            return $this->schoolTerm($value);
        }

        if ($value instanceof Selection) {
            return $this->selection($value, $includeSexo);
        }

        if ($value instanceof Instructor) {
            return $this->instructor($value, $includeSexo);
        }

        if ($value instanceof Department) {
            return $this->department($value);
        }

        if ($value instanceof Requisition) {
            return $this->requisition($value, $includeSexo);
        }

        if ($value instanceof Frequency) {
            return $this->frequency($value, $includeSexo);
        }

        if ($value instanceof Enrollment) {
            return $this->enrollment($value, $includeSexo);
        }

        if ($value instanceof User) {
            return $this->user($value);
        }

        if ($value instanceof Collection) {
            return $value->map(fn ($item) => $this->value($item, $includeSexo))->values()->all();
        }

        if (is_array($value)) {
            return array_map(fn ($item) => $this->value($item, $includeSexo), $value);
        }

        if (is_scalar($value) || $value === null) {
            return $value;
        }

        throw new InvalidArgumentException('Tipo não suportado no contexto de template: '.get_debug_type($value));
    }

    private function student(Student $student, bool $includeSexo): array
    {
        $data = [
            'nompes' => $student->nompes,
            'codpes' => $student->codpes,
            'codema' => $student->codema,
            'sexpes' => $student->sexpes,
        ];

        if ($includeSexo) {
            $data['sexo'] = $student->getSexo();
        }

        return $data;
    }

    private function schoolClass(SchoolClass $schoolclass, bool $includeSexo): array
    {
        return [
            'id' => $schoolclass->id,
            'nomdis' => $schoolclass->nomdis,
            'coddis' => $schoolclass->coddis,
            'codtur' => $schoolclass->codtur,
            'schoolterm' => $schoolclass->schoolterm ? $this->schoolTerm($schoolclass->schoolterm) : null,
            'requisition' => $schoolclass->requisition ? $this->requisition($schoolclass->requisition, $includeSexo) : null,
            'selections' => $schoolclass->selections
                ->map(fn (Selection $selection) => $this->selection($selection, $includeSexo))
                ->values()
                ->all(),
        ];
    }

    private function schoolTerm(SchoolTerm $schoolterm): array
    {
        return [
            'id' => $schoolterm->id,
            'period' => $schoolterm->period,
            'year' => $schoolterm->year,
            'start_date_evaluations' => $schoolterm->start_date_evaluations,
            'end_date_evaluations' => $schoolterm->end_date_evaluations,
            'finished_at' => $schoolterm->finished_at,
        ];
    }

    private function selection(Selection $selection, bool $includeSexo): array
    {
        return [
            'id' => $selection->id,
            'sitatl' => $selection->sitatl,
            'voluntario' => $selection->voluntario,
            'student' => $selection->student ? $this->student($selection->student, $includeSexo) : null,
        ];
    }

    private function instructor(Instructor $instructor, bool $includeSexo): array
    {
        return [
            'id' => $instructor->id,
            'nompes' => $instructor->nompes,
            'codpes' => $instructor->codpes,
            'codema' => $instructor->codema,
            'department' => $instructor->department ? $this->department($instructor->department) : null,
        ];
    }

    private function department(Department $department): array
    {
        return [
            'id' => $department->id,
            'nomset' => $department->nomset,
            'nomabvset' => $department->nomabvset,
            'sglund' => $department->sglund,
        ];
    }

    private function requisition(Requisition $requisition, bool $includeSexo): array
    {
        return [
            'id' => $requisition->id,
            'comments' => $requisition->comments,
            'instructor' => $requisition->instructor ? $this->instructor($requisition->instructor, $includeSexo) : null,
        ];
    }

    private function frequency(Frequency $frequency, bool $includeSexo): array
    {
        return [
            'id' => $frequency->id,
            'month' => $frequency->month,
            'year' => $frequency->year,
            'registered' => $frequency->registered,
            'student' => $frequency->student ? $this->student($frequency->student, $includeSexo) : null,
            'schoolclass' => $frequency->schoolclass ? [
                'id' => $frequency->schoolclass->id,
                'nomdis' => $frequency->schoolclass->nomdis,
                'coddis' => $frequency->schoolclass->coddis,
                'codtur' => $frequency->schoolclass->codtur,
            ] : null,
        ];
    }

    private function enrollment(Enrollment $enrollment, bool $includeSexo): array
    {
        return [
            'id' => $enrollment->id,
            'student' => $enrollment->student ? $this->student($enrollment->student, $includeSexo) : null,
            'schoolclass' => $enrollment->schoolclass ? [
                'id' => $enrollment->schoolclass->id,
                'nomdis' => $enrollment->schoolclass->nomdis,
                'coddis' => $enrollment->schoolclass->coddis,
                'codtur' => $enrollment->schoolclass->codtur,
            ] : null,
        ];
    }

    private function user(User $user): array
    {
        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'codpes' => $user->codpes,
        ];
    }
}
