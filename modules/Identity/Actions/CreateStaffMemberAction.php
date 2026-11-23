<?php

declare(strict_types=1);

namespace Modules\Identity\Actions;

use Modules\Identity\DTO\StaffMemberDTO;
use Modules\Identity\Models\User;

class CreateStaffMemberAction
{
    public function execute(StaffMemberDTO $dto): User
    {
        return User::create([
            'name' => $dto->name,
            'email' => $dto->email,
            'role' => $dto->role,
            'specialization' => $dto->specialization,
            'password' => $dto->password,
            'is_active' => $dto->isActive,
        ]);
    }
}
