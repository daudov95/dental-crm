<?php

declare(strict_types=1);

namespace Modules\Audit\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Audit\Enums\AuditAction;
use Modules\Billing\Models\Payment;
use Modules\Deals\Models\Deal;
use Modules\Identity\Models\User;
use Modules\Patients\Models\Patient;
use Modules\Scheduling\Models\Appointment;

final class AuditLog extends Model
{
    public const null UPDATED_AT = null;

    protected $fillable = [
        'user_id',
        'action',
        'auditable_type',
        'auditable_id',
        'ip_address',
        'metadata',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'action' => AuditAction::class,
            'metadata' => 'array',
            'created_at' => 'immutable_datetime',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function entityLabel(): string
    {
        return match ($this->auditable_type) {
            Patient::class => 'Пациент',
            Deal::class => 'Сделка',
            Appointment::class => 'Визит',
            Payment::class => 'Платеж',
            default => class_basename($this->auditable_type),
        };
    }
}
