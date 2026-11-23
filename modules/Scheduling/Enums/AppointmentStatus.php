<?php

declare(strict_types=1);

namespace Modules\Scheduling\Enums;

enum AppointmentStatus: string
{
    case Scheduled = 'scheduled';
    case Completed = 'completed';
    case NoShow = 'no_show';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Scheduled => 'Записан',
            self::Completed => 'Пришел',
            self::NoShow => 'Не пришел',
            self::Cancelled => 'Отменен',
        };
    }
}
