<?php

declare(strict_types=1);

namespace Modules\Audit\Policies;

use Modules\Audit\Models\AuditLog;
use Modules\Identity\Enums\UserRole;
use Modules\Identity\Models\User;

final readonly class AuditLogPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->role === UserRole::Director;
    }

    public function view(User $user, AuditLog $auditLog): bool
    {
        return $user->role === UserRole::Director;
    }
}
