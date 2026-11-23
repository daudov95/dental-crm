<?php

declare(strict_types=1);

namespace Modules\Identity\Enums;

enum UserRole: string
{
    case Director = 'director';
    case Admin = 'admin';
    case Doctor = 'doctor';

    public function label(): string
    {
        return match ($this) {
            self::Director => 'Руководитель',
            self::Admin => 'Администратор',
            self::Doctor => 'Врач',
        };
    }
}
