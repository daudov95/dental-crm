<?php

declare(strict_types=1);

namespace Modules\Patients\Actions;

use Illuminate\Database\Eloquent\Collection;
use Modules\Patients\Models\Patient;

class RestorePatientAction
{
    public function execute(int $patientId): Collection|Patient
    {
        $patient = Patient::withTrashed()->findOrFail($patientId);
        $patient->restore();

        return $patient->refresh();
    }
}
