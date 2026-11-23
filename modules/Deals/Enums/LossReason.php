<?php

declare(strict_types=1);

namespace Modules\Deals\Enums;

enum LossReason: string
{
    case HighPrice = 'high_price';
    case Competitor = 'competitor';
    case Postponed = 'postponed';
    case NoContact = 'no_contact';
    case ScheduleMismatch = 'schedule_mismatch';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::HighPrice => 'Высокая стоимость',
            self::Competitor => 'Ушел к конкурентам',
            self::Postponed => 'Отложил лечение',
            self::NoContact => 'Не выходит на связь',
            self::ScheduleMismatch => 'Не подошел график врача',
            self::Other => 'Другое',
        };
    }
}
