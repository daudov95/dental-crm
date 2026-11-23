<?php

declare(strict_types=1);

namespace Modules\Deals\Enums;

enum DealStatus: string
{
    case New = 'new';
    case Consultation = 'consultation';
    case TreatmentPlan = 'treatment_plan';
    case InProgress = 'in_progress';
    case Completed = 'completed';
    case Lost = 'lost';

    public function label(): string
    {
        return match ($this) {
            self::New => 'Новая заявка',
            self::Consultation => 'Консультация',
            self::TreatmentPlan => 'План лечения',
            self::InProgress => 'В работе',
            self::Completed => 'Завершена',
            self::Lost => 'Отказ',
        };
    }
}
