<?php

namespace App\Policies;

use App\Models\SelfEvaluation;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class SelfEvaluationPolicy
{
    use HandlesAuthorization;

    public function view(User $user, SelfEvaluation $selfEvaluation): bool
    {
        return $this->owns($user, $selfEvaluation)
            || $user->hasPermissionTo('Visualizar auto avaliações');
    }

    public function update(User $user, SelfEvaluation $selfEvaluation): bool
    {
        return $user->hasRole('Aluno') && $this->owns($user, $selfEvaluation);
    }

    private function owns(User $user, SelfEvaluation $selfEvaluation): bool
    {
        return (string) $user->codpes === (string) $selfEvaluation->student->codpes;
    }
}
