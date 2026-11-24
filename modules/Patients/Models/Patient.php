<?php

declare(strict_types=1);

namespace Modules\Patients\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Patients\Enums\PatientSource;

class Patient extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'full_name',
        'phone',
        'birth_date',
        'source',
        'notes',
        'consent_given',
    ];

    protected function casts(): array
    {
        return [
            'birth_date' => 'date',
            'source' => PatientSource::class,
            'consent_given' => 'boolean',
        ];
    }
}
