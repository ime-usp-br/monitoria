<?php

namespace App\Policies;

use App\Models\SchoolRecord;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class SchoolRecordPolicy
{
    use HandlesAuthorization;

    public function view(User $user, SchoolRecord $schoolRecord): bool
    {
        return $this->owns($user, $schoolRecord)
            || $user->hasPermissionTo('baixar histórico escolar');
    }

    public function create(User $user): bool
    {
        return $user->hasRole('Aluno');
    }

    public function update(User $user, SchoolRecord $schoolRecord): bool
    {
        return $user->hasRole('Aluno') && $this->owns($user, $schoolRecord);
    }

    public function download(User $user, SchoolRecord $schoolRecord): bool
    {
        return $user->hasPermissionTo('baixar histórico escolar');
    }

    private function owns(User $user, SchoolRecord $schoolRecord): bool
    {
        return (string) $user->codpes === (string) $schoolRecord->student->codpes;
    }
}
