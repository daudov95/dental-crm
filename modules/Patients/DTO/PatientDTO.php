<?php

declare(strict_types=1);

namespace Modules\Patients\DTO;

use Modules\Patients\Enums\PatientSource;

readonly class PatientDTO
{
    public function __construct(
        public string $fullName,
        public string $phone,
        public ?string $birthDate,
        public PatientSource $source,
        public ?string $notes,
        public bool $consentGiven,
    ) {}
}
