<?php

declare(strict_types=1);

namespace Modules\Scheduling\Policies;

use Modules\Identity\Enums\UserRole;
use Modules\Identity\Models\User;
use Modules\Scheduling\Models\Appointment;

final class AppointmentPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Appointment $appointment): bool
    {
        if ($user->isStaffManager()) {
            return true;
        }

        return $user->role === UserRole::Doctor && $appointment->doctor_id === $user->id;
    }

    public function create(User $user): bool
    {
        return $user->isStaffManager();
    }

    public function update(User $user, Appointment $appointment): bool
    {
        return $user->isStaffManager();
    }

    public function updateStatus(User $user, Appointment $appointment): bool
    {
        if ($user->isStaffManager()) {
            return true;
        }

        return $user->role === UserRole::Doctor && $appointment->doctor_id === $user->id;
    }

    public function delete(User $user, Appointment $appointment): bool
    {
        return $user->isStaffManager();
    }
}
