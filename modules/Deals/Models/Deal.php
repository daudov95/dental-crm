<?php

declare(strict_types=1);

namespace Modules\Deals\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Deals\Enums\DealStatus;
use Modules\Deals\Enums\LossReason;
use Modules\Identity\Enums\UserRole;
use Modules\Identity\Models\User;
use Modules\Patients\Models\Patient;

class Deal extends Model
{
    protected $fillable = [
        'patient_id',
        'doctor_id',
        'title',
        'status',
        'sort_order',
        'loss_reason',
        'total_amount',
        'is_installment',
        'next_payment_date',
        'is_overdue',
    ];

    protected function casts(): array
    {
        return [
            'status' => DealStatus::class,
            'sort_order' => 'integer',
            'loss_reason' => LossReason::class,
            'total_amount' => 'decimal:2',
            'is_installment' => 'boolean',
            'next_payment_date' => 'date',
            'is_overdue' => 'boolean',
        ];
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class, 'patient_id')->withTrashed();
    }

    public function doctor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'doctor_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(DealItem::class, 'deal_id');
    }

    public function scopeVisibleFor(Builder $query, User $user): Builder
    {
        if ($user->role === UserRole::Doctor) {
            return $query->where('doctor_id', $user->id);
        }

        return $query;
    }
}
