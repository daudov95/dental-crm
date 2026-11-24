<?php

declare(strict_types=1);

namespace Modules\Scheduling\Actions;

use Modules\Scheduling\Enums\AppointmentStatus;
use Modules\Scheduling\Events\AppointmentStatusUpdated;
use Modules\Scheduling\Models\Appointment;

final readonly class UpdateAppointmentStatusAction
{
    public function execute(
        Appointment $appointment,
        AppointmentStatus $newStatus,
        ?string $notes = null,
        ?int $actorId = null,
        ?string $ipAddress = null,
    ): Appointment {
        $previousStatus = $appointment->status;

        $data = ['status' => $newStatus];
        if ($notes !== null) {
            $data['notes'] = $notes;
        }

        $appointment->update($data);

        if ($previousStatus !== $newStatus) {
            AppointmentStatusUpdated::dispatch($appointment, $previousStatus, $newStatus, $actorId, $ipAddress);
        }

        return $appointment->refresh();
    }
}
