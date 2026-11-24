<?php

namespace App\Policies;

use App\Models\SchoolClass;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class SchoolClassPolicy
{
    use HandlesAuthorization;

    public function update(User $user, SchoolClass $schoolClass): bool
    {
        if ($user->hasAnyRole(['Administrador', 'Secretaria'])) {
            return true;
        }

        return $user->hasPermissionTo('editar turma')
            && $schoolClass->isInstructor($user->codpes);
    }

    public function viewEnrollments(User $user, SchoolClass $schoolClass): bool
    {
        return $user->hasPermissionTo('visualizar todos inscritos')
            || $schoolClass->isInstructor($user->codpes);
    }
}
