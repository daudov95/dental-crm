<?php

declare(strict_types=1);

namespace Modules\Patients\Actions;

use Modules\Patients\DTO\PatientDTO;
use Modules\Patients\Events\PatientUpdated;
use Modules\Patients\Models\Patient;

class UpdatePatientAction
{
    public function execute(Patient $patient, PatientDTO $dto, ?int $actorId = null, ?string $ipAddress = null): Patient
    {
        $patient->fill([
            'full_name' => $dto->fullName,
            'phone' => $dto->phone,
            'birth_date' => $dto->birthDate,
            'source' => $dto->source,
            'notes' => $dto->notes,
            'consent_given' => $dto->consentGiven,
        ]);

        $changes = $patient->getDirty();
        $patient->save();

        if ($changes !== []) {
            PatientUpdated::dispatch($patient, $changes, $actorId, $ipAddress);
        }

        return $patient->refresh();
    }
}
