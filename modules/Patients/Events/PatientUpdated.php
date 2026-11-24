<?php

declare(strict_types=1);

namespace Modules\Patients\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Modules\Patients\Models\Patient;

class PatientUpdated
{
    use Dispatchable;
    use SerializesModels;

    /**
     * @param array<string, mixed> $changes
     */
    public function __construct(
        public readonly Patient $patient,
        public readonly array $changes,
        public readonly ?int $actorId,
        public readonly ?string $ipAddress = null,
    ) {}
}
