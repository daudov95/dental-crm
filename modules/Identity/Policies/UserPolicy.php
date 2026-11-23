<?php

declare(strict_types=1);

namespace Modules\Identity\Policies;

use Modules\Identity\Models\User;

class UserPolicy
{
    public function viewAny(User $authUser): bool
    {
        return $authUser->isDirector();
    }

    public function create(User $authUser): bool
    {
        return $authUser->isDirector();
    }

    public function update(User $authUser, User $target): bool
    {
        return $authUser->isDirector();
    }

    public function toggleActive(User $authUser, User $target): bool
    {
        return $authUser->isDirector() && $authUser->id !== $target->id;
    }
}
