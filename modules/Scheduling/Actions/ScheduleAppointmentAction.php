<?php

declare(strict_types=1);

namespace Modules\Scheduling\Actions;

use InvalidArgumentException;
use Modules\Scheduling\DTO\AppointmentDTO;
use Modules\Scheduling\Enums\AppointmentStatus;
use Modules\Scheduling\Events\AppointmentScheduled;
use Modules\Scheduling\Exceptions\AppointmentConflictException;
use Modules\Scheduling\Models\Appointment;

final readonly class ScheduleAppointmentAction
{
    public function __construct(
        private CheckDoctorAvailabilityAction $availability,
    ) {}

    public function execute(AppointmentDTO $dto, ?int $actorId = null, ?string $ipAddress = null): Appointment
    {
        if ($dto->endsAt->lessThanOrEqualTo($dto->startsAt)) {
            throw new InvalidArgumentException('Время окончания приема должно быть позже времени начала.');
        }

        if (
            $dto->status !== AppointmentStatus::Cancelled
            && $this->availability->hasConflict($dto->doctorId, $dto->startsAt, $dto->endsAt)
        ) {
            throw new AppointmentConflictException('У выбранного врача уже есть запись на указанное время.');
        }

        $appointment = Appointment::create([
            'patient_id' => $dto->patientId,
            'doctor_id' => $dto->doctorId,
            'deal_id' => $dto->dealId,
            'starts_at' => $dto->startsAt,
            'ends_at' => $dto->endsAt,
            'status' => $dto->status,
            'notes' => $dto->notes,
        ]);

        AppointmentScheduled::dispatch($appointment, $actorId, $ipAddress);

        return $appointment->load(['patient', 'doctor', 'deal']);
    }
}
