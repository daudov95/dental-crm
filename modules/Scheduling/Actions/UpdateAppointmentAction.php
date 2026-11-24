<?php

declare(strict_types=1);

namespace Modules\Scheduling\Actions;

use InvalidArgumentException;
use Modules\Scheduling\DTO\AppointmentDTO;
use Modules\Scheduling\Enums\AppointmentStatus;
use Modules\Scheduling\Events\AppointmentStatusUpdated;
use Modules\Scheduling\Exceptions\AppointmentConflictException;
use Modules\Scheduling\Models\Appointment;

final readonly class UpdateAppointmentAction
{
    public function __construct(
        private CheckDoctorAvailabilityAction $availability,
    ) {}

    public function execute(
        Appointment $appointment,
        AppointmentDTO $dto,
        ?int $actorId = null,
        ?string $ipAddress = null,
    ): Appointment {
        if ($dto->endsAt->lessThanOrEqualTo($dto->startsAt)) {
            throw new InvalidArgumentException('Время окончания приема должно быть позже времени начала.');
        }

        if (
            $dto->status !== AppointmentStatus::Cancelled
            && $this->availability->hasConflict($dto->doctorId, $dto->startsAt, $dto->endsAt, $appointment->id)
        ) {
            throw new AppointmentConflictException('У выбранного врача уже есть запись на указанное время.');
        }

        $previousStatus = $appointment->status;

        $appointment->update([
            'patient_id' => $dto->patientId,
            'doctor_id' => $dto->doctorId,
            'deal_id' => $dto->dealId,
            'starts_at' => $dto->startsAt,
            'ends_at' => $dto->endsAt,
            'status' => $dto->status,
            'notes' => $dto->notes,
        ]);

        if ($previousStatus !== $appointment->status) {
            AppointmentStatusUpdated::dispatch($appointment, $previousStatus, $appointment->status, $actorId, $ipAddress);
        }

        return $appointment->refresh()->load(['patient', 'doctor', 'deal']);
    }
}
