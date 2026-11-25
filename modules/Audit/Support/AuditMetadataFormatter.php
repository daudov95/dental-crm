<?php

declare(strict_types=1);

namespace Modules\Audit\Support;

use Carbon\CarbonImmutable;
use Modules\Billing\Enums\PaymentMethod;
use Modules\Deals\Enums\DealStatus;
use Modules\Deals\Enums\LossReason;
use Modules\Patients\Enums\PatientSource;
use Modules\Scheduling\Enums\AppointmentStatus;
use Throwable;

final readonly class AuditMetadataFormatter
{
    /**
     * @param array<string, mixed>|null $metadata
     * @return list<array{label: string, value: string}>
     */
    public function format(?array $metadata): array
    {
        if ($metadata === null || $metadata === []) {
            return [];
        }

        $rows = [];

        if (isset($metadata['from_status'], $metadata['to_status'])) {
            $from = $this->formatStatus((string) $metadata['from_status']);
            $to = $this->formatStatus((string) $metadata['to_status']);
            $rows[] = [
                'label' => 'Изменение статуса',
                'value' => "{$from} → {$to}",
            ];
        }

        foreach ($metadata as $key => $value) {
            if ($key === 'from_status' || $key === 'to_status') {
                continue;
            }

            if ($key === 'changes' && is_array($value)) {
                foreach ($value as $field => $fieldValue) {
                    $rows[] = [
                        'label' => 'Изменено: ' . $this->fieldLabel((string) $field),
                        'value' => $this->formatValue((string) $field, $fieldValue),
                    ];
                }

                continue;
            }

            if ($value === null && $key === 'loss_reason') {
                continue;
            }

            $rows[] = [
                'label' => $this->fieldLabel((string) $key),
                'value' => $this->formatValue((string) $key, $value),
            ];
        }

        return $rows;
    }

    private function fieldLabel(string $key): string
    {
        return match ($key) {
            'full_name' => 'ФИО пациента',
            'phone' => 'Телефон',
            'birth_date' => 'Дата рождения',
            'source' => 'Канал обращения',
            'notes' => 'Заметки',
            'consent_given' => 'Согласие 152-ФЗ',
            'title' => 'Название сделки',
            'patient_id' => 'Пациент',
            'doctor_id' => 'Врач',
            'deal_id' => 'Сделка',
            'status' => 'Статус',
            'loss_reason' => 'Причина отказа',
            'total_amount' => 'Сумма сделки',
            'amount' => 'Сумма платежа',
            'is_installment' => 'Рассрочка',
            'next_payment_date' => 'Дата след. платежа',
            'method' => 'Способ оплаты',
            'starts_at' => 'Начало приема',
            'ends_at' => 'Окончание приема',
            'paid_at' => 'Дата и время оплаты',
            default => $key,
        };
    }

    private function formatValue(string $key, mixed $value): string
    {
        if ($value === null || $value === '') {
            return '—';
        }

        if (is_bool($value)) {
            return match ($key) {
                'consent_given' => $value ? 'Подписано' : 'Не оформлено',
                default => $value ? 'Да' : 'Нет',
            };
        }

        if ($key === 'source') {
            return PatientSource::tryFrom((string) $value)?->label() ?? (string) $value;
        }

        if ($key === 'status') {
            return $this->formatStatus((string) $value);
        }

        if ($key === 'loss_reason') {
            return LossReason::tryFrom((string) $value)?->label() ?? (string) $value;
        }

        if ($key === 'method') {
            return PaymentMethod::tryFrom((string) $value)?->label() ?? (string) $value;
        }

        if (($key === 'amount' || $key === 'total_amount') && is_numeric($value)) {
            return number_format((float) $value, 0, ',', ' ') . ' ₽';
        }

        if (in_array($key, ['patient_id', 'doctor_id', 'deal_id'], true)) {
            return '#' . (string) $value;
        }

        if (in_array($key, ['starts_at', 'ends_at', 'paid_at'], true) && is_string($value)) {
            try {
                return CarbonImmutable::parse($value)->format('d.m.Y H:i');
            } catch (Throwable) {
                return $value;
            }
        }

        if (in_array($key, ['birth_date', 'next_payment_date'], true) && is_string($value)) {
            try {
                return CarbonImmutable::parse($value)->format('d.m.Y');
            } catch (Throwable) {
                return $value;
            }
        }

        if (is_array($value)) {
            return (string) json_encode($value, JSON_UNESCAPED_UNICODE);
        }

        return (string) $value;
    }

    private function formatStatus(string $value): string
    {
        return DealStatus::tryFrom($value)?->label()
            ?? AppointmentStatus::tryFrom($value)?->label()
            ?? $value;
    }
}
