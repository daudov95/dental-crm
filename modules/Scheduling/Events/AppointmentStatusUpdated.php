<?php

declare(strict_types=1);

namespace Modules\Scheduling\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Modules\Scheduling\Enums\AppointmentStatus;
use Modules\Scheduling\Models\Appointment;

final readonly class AppointmentStatusUpdated
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public Appointment       $appointment,
        public AppointmentStatus $fromStatus,
        public AppointmentStatus $toStatus,
        public ?int              $actorId = null,
        public ?string           $ipAddress = null,
    ) {}
}
