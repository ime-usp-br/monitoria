<?php

namespace App\Policies;

use App\Models\Enrollment;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class EnrollmentPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('visualizar todos inscritos');
    }

    public function view(User $user, Enrollment $enrollment): bool
    {
        return $this->owns($user, $enrollment)
            || $user->hasPermissionTo('visualizar todos inscritos');
    }

    public function create(User $user): bool
    {
        return $user->hasRole('Aluno');
    }

    public function update(User $user, Enrollment $enrollment): bool
    {
        return $user->hasRole('Aluno')
            && $user->hasPermissionTo('editar inscrição')
            && $this->owns($user, $enrollment);
    }

    public function delete(User $user, Enrollment $enrollment): bool
    {
        return $user->hasRole('Aluno')
            && $user->hasPermissionTo('deletar inscrição')
            && $this->owns($user, $enrollment);
    }

    private function owns(User $user, Enrollment $enrollment): bool
    {
        return (string) $user->codpes === (string) $enrollment->student->codpes;
    }
}
