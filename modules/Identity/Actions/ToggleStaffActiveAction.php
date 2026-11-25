<?php

declare(strict_types=1);

namespace Modules\Identity\Actions;

use Modules\Identity\Models\User;

class ToggleStaffActiveAction
{
    public function execute(User $user): User
    {
        $user->update(['is_active' => !$user->is_active]);

        return $user->refresh();
    }
}
