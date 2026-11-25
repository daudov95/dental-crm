<?php

declare(strict_types=1);

namespace Modules\Audit\Enums;

enum AuditAction: string
{
    case PatientCreated = 'patient.created';
    case PatientUpdated = 'patient.updated';
    case PatientArchived = 'patient.archived';
    case DealCreated = 'deal.created';
    case DealUpdated = 'deal.updated';
    case DealStageChanged = 'deal.stage_changed';
    case AppointmentScheduled = 'appointment.scheduled';
    case AppointmentStatusUpdated = 'appointment.status_updated';
    case PaymentRecorded = 'payment.recorded';

    public function label(): string
    {
        return match ($this) {
            self::PatientCreated => 'Создан пациент',
            self::PatientUpdated => 'Обновлен пациент',
            self::PatientArchived => 'Пациент отправлен в архив',
            self::DealCreated => 'Создана сделка',
            self::DealUpdated => 'Обновлена сделка',
            self::DealStageChanged => 'Смена этапа сделки',
            self::AppointmentScheduled => 'Запись на прием',
            self::AppointmentStatusUpdated => 'Смена статуса визита',
            self::PaymentRecorded => 'Проведен платеж',
        };
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::PatientCreated, self::DealCreated, self::AppointmentScheduled => 'bg-emerald-50 text-emerald-700 border-emerald-200',
            self::PatientUpdated, self::DealUpdated, self::DealStageChanged, self::AppointmentStatusUpdated => 'bg-sky-50 text-sky-700 border-sky-200',
            self::PatientArchived => 'bg-rose-50 text-rose-700 border-rose-200',
            self::PaymentRecorded => 'bg-amber-50 text-amber-800 border-amber-200',
        };
    }
}
