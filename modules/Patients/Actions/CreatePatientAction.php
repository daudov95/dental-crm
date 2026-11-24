<?php

declare(strict_types=1);

namespace Modules\Patients\Actions;

use Modules\Patients\DTO\PatientDTO;
use Modules\Patients\Events\PatientCreated;
use Modules\Patients\Models\Patient;

class CreatePatientAction
{
    public function execute(PatientDTO $dto, ?int $actorId = null, ?string $ipAddress = null): Patient
    {
        $patient = Patient::create([
            'full_name' => $dto->fullName,
            'phone' => $dto->phone,
            'birth_date' => $dto->birthDate,
            'source' => $dto->source,
            'notes' => $dto->notes,
            'consent_given' => $dto->consentGiven,
        ]);

        PatientCreated::dispatch($patient, $actorId, $ipAddress);

        return $patient;
    }
}
