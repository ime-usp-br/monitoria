<?php

namespace App\Policies;

use App\Models\InstructorEvaluation;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class InstructorEvaluationPolicy
{
    use HandlesAuthorization;

    public function view(User $user, InstructorEvaluation $instructorEvaluation): bool
    {
        return $this->owns($user, $instructorEvaluation)
            || $user->hasPermissionTo('Visualizar avaliações dos docentes');
    }

    public function update(User $user, InstructorEvaluation $instructorEvaluation): bool
    {
        return $user->hasRole('Docente') && $this->owns($user, $instructorEvaluation);
    }

    private function owns(User $user, InstructorEvaluation $instructorEvaluation): bool
    {
        return (string) $user->codpes === (string) $instructorEvaluation->instructor->codpes;
    }
}
