<?php

declare(strict_types=1);

namespace Modules\Patients\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Modules\Patients\Models\Patient;

class PatientArchived
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public readonly Patient $patient,
        public readonly ?int $actorId,
        public readonly ?string $ipAddress = null,
    ) {}
}
