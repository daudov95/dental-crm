<?php

declare(strict_types=1);

namespace Modules\Patients\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Deals\Models\Deal;
use Modules\Identity\Enums\UserRole;
use Modules\Identity\Models\User;
use Modules\Patients\Enums\PatientSource;
use Modules\Scheduling\Models\Appointment;

final class Patient extends Model
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

    public function deals(): HasMany
    {
        return $this->hasMany(Deal::class, 'patient_id');
    }

    public function appointments(): HasMany
    {
        return $this->hasMany(Appointment::class, 'patient_id');
    }

    public function scopeVisibleFor(Builder $query, User $user): Builder
    {
        if ($user->isStaffManager()) {
            return $query;
        }

        if ($user->role === UserRole::Doctor) {
            return $query->where(function (Builder $sub) use ($user): void {
                $sub->whereHas('deals', fn (Builder $d): Builder => $d->where('doctor_id', $user->id))
                    ->orWhereHas('appointments', fn (Builder $a): Builder => $a->where('doctor_id', $user->id));
            });
        }

        return $query->whereRaw('1 = 0');
    }
}
