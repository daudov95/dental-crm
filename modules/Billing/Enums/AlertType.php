<?php

declare(strict_types=1);

namespace Modules\Billing\Enums;

enum AlertType: string
{
    case OverdueInstallment = 'overdue_installment';
    case TomorrowReminder = 'tomorrow_reminder';

    public function label(): string
    {
        return match ($this) {
            self::OverdueInstallment => 'Просрочка по рассрочке',
            self::TomorrowReminder => 'Напоминание о визите на завтра',
        };
    }
}
