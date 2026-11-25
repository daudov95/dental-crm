<?php

declare(strict_types=1);

namespace Modules\Scheduling\Actions;

use Carbon\CarbonInterface;
use Modules\Scheduling\Models\Appointment;

final readonly class CheckDoctorAvailabilityAction
{
    public function hasConflict(
        int $doctorId,
        CarbonInterface $startsAt,
        CarbonInterface $endsAt,
        ?int $ignoreAppointmentId = null,
    ): bool {
        return Appointment::query()
            ->where('doctor_id', $doctorId)
            ->active()
            ->when($ignoreAppointmentId !== null, fn ($q) => $q->where('id', '!=', $ignoreAppointmentId))
            ->where('starts_at', '<', $endsAt)
            ->where('ends_at', '>', $startsAt)
            ->exists();
    }
}
