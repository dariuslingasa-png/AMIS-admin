<?php

namespace App\Policies;

use App\Enums\AccountStatus;
use App\Enums\UserRole;
use App\Models\Student;
use App\Models\User;

class StudentPolicy
{
    public function viewPortal(User $user): bool
    {
        return $user->role === UserRole::Student->value
            && ($user->account_status ?? AccountStatus::Verified->value) === AccountStatus::Verified->value;
    }

    public function manage(User $user, Student $student): bool
    {
        return $user->id === $student->user_id;
    }
}
