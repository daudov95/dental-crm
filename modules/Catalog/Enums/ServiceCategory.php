<?php

declare(strict_types=1);

namespace Modules\Catalog\Enums;

enum ServiceCategory: string
{
    case Therapy = 'therapy';
    case Orthodontics = 'orthodontics';
    case Implantology = 'implantology';
    case Surgery = 'surgery';
    case Prosthetics = 'prosthetics';
    case Diagnostics = 'diagnostics';

    public function label(): string
    {
        return match ($this) {
            self::Therapy => 'Терапия',
            self::Orthodontics => 'Ортодонтия',
            self::Implantology => 'Имплантация',
            self::Surgery => 'Хирургия',
            self::Prosthetics => 'Протезирование',
            self::Diagnostics => 'Диагностика',
        };
    }
}
