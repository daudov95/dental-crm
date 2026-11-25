<?php

declare(strict_types=1);

namespace Modules\Identity\Actions;

use Modules\Identity\DTO\StaffMemberDTO;
use Modules\Identity\Models\User;

class UpdateStaffMemberAction
{
    public function execute(User $user, StaffMemberDTO $dto): User
    {
        $data = [
            'name' => $dto->name,
            'email' => $dto->email,
            'role' => $dto->role,
            'specialization' => $dto->specialization,
            'is_active' => $dto->isActive,
        ];

        if ($dto->password !== null && $dto->password !== '') {
            $data['password'] = $dto->password;
        }

        $user->update($data);

        return $user->refresh();
    }
}
