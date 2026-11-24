<?php

namespace App\Policies;

use App\Models\Requisition;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class RequisitionPolicy
{
    use HandlesAuthorization;

    public function view(User $user, Requisition $requisition): bool
    {
        return $user->hasRole('Administrador')
            || $this->owns($user, $requisition);
    }

    public function update(User $user, Requisition $requisition): bool
    {
        if ($user->hasRole('Administrador')) {
            return true;
        }

        return $user->hasPermissionTo('editar solicitação de monitor')
            && $this->owns($user, $requisition);
    }

    private function owns(User $user, Requisition $requisition): bool
    {
        return $requisition->schoolclass->isInstructor($user->codpes);
    }
}
