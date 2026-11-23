<?php

declare(strict_types=1);

namespace Modules\Catalog\Policies;

use Modules\Catalog\Models\Service;
use Modules\Identity\Models\User;

class ServicePolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return $user->isStaffManager();
    }

    public function update(User $user, Service $service): bool
    {
        return $user->isStaffManager();
    }

    public function toggleActive(User $user, Service $service): bool
    {
        return $user->isStaffManager();
    }
}
