<?php

declare(strict_types=1);

namespace Modules\Patients\Policies;

use Modules\Identity\Enums\UserRole;
use Modules\Identity\Models\User;
use Modules\Patients\Models\Patient;

final readonly class PatientPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Patient $patient): bool
    {
        if ($user->isStaffManager()) {
            return true;
        }

        if ($user->role === UserRole::Doctor) {
            return $patient->deals()->where('doctor_id', $user->id)->exists()
                || $patient->appointments()->where('doctor_id', $user->id)->exists();
        }

        return false;
    }

    public function create(User $user): bool
    {
        return $user->isStaffManager();
    }

    public function update(User $user, Patient $patient): bool
    {
        return $user->isStaffManager();
    }

    public function delete(User $user, Patient $patient): bool
    {
        return $user->isStaffManager();
    }

    public function restore(User $user, Patient $patient): bool
    {
        return $user->isStaffManager();
    }
}
