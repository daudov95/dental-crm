<?php

declare(strict_types=1);

namespace Modules\Patients\Actions;

use Modules\Patients\Events\PatientArchived;
use Modules\Patients\Models\Patient;

class ArchivePatientAction
{
    public function execute(Patient $patient, ?int $actorId = null, ?string $ipAddress = null): void
    {
        $patient->delete();

        PatientArchived::dispatch($patient, $actorId, $ipAddress);
    }
}
