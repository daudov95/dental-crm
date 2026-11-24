<?php

declare(strict_types=1);

namespace Modules\Deals\Policies;

use Modules\Deals\Models\Deal;
use Modules\Identity\Enums\UserRole;
use Modules\Identity\Models\User;

class DealPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Deal $deal): bool
    {
        if ($user->isStaffManager()) {
            return true;
        }

        return $user->role === UserRole::Doctor && $deal->doctor_id === $user->id;
    }

    public function create(User $user): bool
    {
        return $user->isStaffManager();
    }

    public function update(User $user, Deal $deal): bool
    {
        return $user->isStaffManager();
    }

    public function moveStage(User $user, Deal $deal): bool
    {
        return $user->isStaffManager();
    }

    public function delete(User $user, Deal $deal): bool
    {
        return $user->isStaffManager();
    }
}
