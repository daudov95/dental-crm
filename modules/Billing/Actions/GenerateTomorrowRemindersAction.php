<?php

declare(strict_types=1);

namespace Modules\Billing\Actions;

use Carbon\CarbonImmutable;
use Modules\Billing\Models\SystemAlert;
use Modules\Scheduling\Enums\AppointmentStatus;
use Modules\Scheduling\Models\Appointment;

final readonly class GenerateTomorrowRemindersAction
{
    public function execute(): bool
    {
        $tomorrowStart = CarbonImmutable::tomorrow()->startOfDay();
        $tomorrowEnd = CarbonImmutable::tomorrow()->endOfDay();

        $count = Appointment::query()
            ->whereBetween('starts_at', [$tomorrowStart, $tomorrowEnd])
            ->where('status', AppointmentStatus::Scheduled->value)
            ->count();

        if ($count === 0) {
            return false;
        }

        $alreadyCreatedToday = SystemAlert::query()
            ->where('type', 'tomorrow_reminders')
            ->whereDate('created_at', CarbonImmutable::today()->toDateString())
            ->exists();

        if ($alreadyCreatedToday) {
            return false;
        }

        SystemAlert::create([
            'type' => 'tomorrow_reminders',
            'title' => "Напоминания о визитах на {$tomorrowStart->format('d.m.Y')}",
            'message' => "На завтра запланировано визитов: {$count}. Откройте вкладку «Напоминания на завтра» в расписании для обзвона и подтверждения.",
            'entity_type' => Appointment::class,
            'entity_id' => null,
            'is_read' => false,
        ]);

        return true;
    }
}
