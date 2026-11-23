<?php

declare(strict_types=1);

namespace Modules\Identity\DTO;

use Modules\Identity\Enums\UserRole;

readonly class StaffMemberDTO
{
    public function __construct(
        public string $name,
        public string $email,
        public UserRole $role,
        public ?string $specialization,
        public ?string $password = null,
        public bool $isActive = true,
    ) {}
}
