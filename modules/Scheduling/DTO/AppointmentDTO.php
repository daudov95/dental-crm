<?php

declare(strict_types=1);

namespace Modules\Scheduling\DTO;

use Carbon\CarbonImmutable;
use Modules\Scheduling\Enums\AppointmentStatus;

final readonly class AppointmentDTO
{
    public function __construct(
        public int $patientId,
        public int $doctorId,
        public ?int $dealId,
        public CarbonImmutable $startsAt,
        public CarbonImmutable $endsAt,
        public AppointmentStatus $status = AppointmentStatus::Scheduled,
        public ?string $notes = null,
    ) {}
}
