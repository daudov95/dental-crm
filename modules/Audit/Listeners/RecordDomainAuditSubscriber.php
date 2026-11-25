<?php

declare(strict_types=1);

namespace Modules\Audit\Listeners;

use BackedEnum;
use DateTimeInterface;
use Illuminate\Events\Dispatcher;
use Modules\Audit\Actions\RecordAuditLogAction;
use Modules\Audit\DTO\AuditLogDTO;
use Modules\Audit\Enums\AuditAction;
use Modules\Billing\Events\PaymentRecorded;
use Modules\Billing\Models\Payment;
use Modules\Deals\Events\DealCreated;
use Modules\Deals\Events\DealStageChanged;
use Modules\Deals\Events\DealUpdated;
use Modules\Deals\Models\Deal;
use Modules\Patients\Events\PatientArchived;
use Modules\Patients\Events\PatientCreated;
use Modules\Patients\Events\PatientUpdated;
use Modules\Patients\Models\Patient;
use Modules\Scheduling\Events\AppointmentScheduled;
use Modules\Scheduling\Events\AppointmentStatusUpdated;
use Modules\Scheduling\Models\Appointment;

final readonly class RecordDomainAuditSubscriber
{
    public function __construct(
        private RecordAuditLogAction $recordAction,
    ) {}

    public function handlePatientCreated(PatientCreated $event): void
    {
        $this->recordAction->execute(new AuditLogDTO(
            action: AuditAction::PatientCreated,
            auditableType: Patient::class,
            auditableId: $event->patient->id,
            userId: $this->resolveActorId($event->actorId),
            ipAddress: $this->resolveIp($event->ipAddress),
            metadata: [
                'full_name' => $event->patient->full_name,
                'phone' => $event->patient->phone,
                'source' => $event->patient->source->value,
                'consent_given' => $event->patient->consent_given,
            ],
        ));
    }

    public function handlePatientUpdated(PatientUpdated $event): void
    {
        $this->recordAction->execute(new AuditLogDTO(
            action: AuditAction::PatientUpdated,
            auditableType: Patient::class,
            auditableId: $event->patient->id,
            userId: $this->resolveActorId($event->actorId),
            ipAddress: $this->resolveIp($event->ipAddress),
            metadata: [
                'full_name' => $event->patient->full_name,
                'changes' => $this->normalizeChanges($event->changes),
            ],
        ));
    }

    public function handlePatientArchived(PatientArchived $event): void
    {
        $this->recordAction->execute(new AuditLogDTO(
            action: AuditAction::PatientArchived,
            auditableType: Patient::class,
            auditableId: $event->patient->id,
            userId: $this->resolveActorId($event->actorId),
            ipAddress: $this->resolveIp($event->ipAddress),
            metadata: [
                'full_name' => $event->patient->full_name,
                'phone' => $event->patient->phone,
            ],
        ));
    }

    public function handleDealCreated(DealCreated $event): void
    {
        $this->recordAction->execute(new AuditLogDTO(
            action: AuditAction::DealCreated,
            auditableType: Deal::class,
            auditableId: $event->deal->id,
            userId: $this->resolveActorId($event->actorId),
            ipAddress: $this->resolveIp($event->ipAddress),
            metadata: [
                'title' => $event->deal->title,
                'patient_id' => $event->deal->patient_id,
                'status' => $event->deal->status->value,
                'total_amount' => (float) $event->deal->total_amount,
                'is_installment' => (bool) $event->deal->is_installment,
            ],
        ));
    }

    public function handleDealUpdated(DealUpdated $event): void
    {
        $this->recordAction->execute(new AuditLogDTO(
            action: AuditAction::DealUpdated,
            auditableType: Deal::class,
            auditableId: $event->deal->id,
            userId: $this->resolveActorId($event->actorId),
            ipAddress: $this->resolveIp($event->ipAddress),
            metadata: [
                'title' => $event->deal->title,
                'changes' => $this->normalizeChanges($event->changes),
            ],
        ));
    }

    public function handleDealStageChanged(DealStageChanged $event): void
    {
        $this->recordAction->execute(new AuditLogDTO(
            action: AuditAction::DealStageChanged,
            auditableType: Deal::class,
            auditableId: $event->deal->id,
            userId: $this->resolveActorId($event->actorId),
            ipAddress: $this->resolveIp($event->ipAddress),
            metadata: [
                'title' => $event->deal->title,
                'from_status' => $event->fromStatus->value,
                'to_status' => $event->toStatus->value,
                'loss_reason' => $event->lossReason?->value,
            ],
        ));
    }

    public function handleAppointmentScheduled(AppointmentScheduled $event): void
    {
        $this->recordAction->execute(new AuditLogDTO(
            action: AuditAction::AppointmentScheduled,
            auditableType: Appointment::class,
            auditableId: $event->appointment->id,
            userId: $this->resolveActorId($event->actorId),
            ipAddress: $this->resolveIp($event->ipAddress),
            metadata: [
                'patient_id' => $event->appointment->patient_id,
                'doctor_id' => $event->appointment->doctor_id,
                'deal_id' => $event->appointment->deal_id,
                'starts_at' => $event->appointment->starts_at?->toIso8601String(),
                'ends_at' => $event->appointment->ends_at?->toIso8601String(),
            ],
        ));
    }

    public function handleAppointmentStatusUpdated(AppointmentStatusUpdated $event): void
    {
        $this->recordAction->execute(new AuditLogDTO(
            action: AuditAction::AppointmentStatusUpdated,
            auditableType: Appointment::class,
            auditableId: $event->appointment->id,
            userId: $this->resolveActorId($event->actorId),
            ipAddress: $this->resolveIp($event->ipAddress),
            metadata: [
                'patient_id' => $event->appointment->patient_id,
                'doctor_id' => $event->appointment->doctor_id,
                'from_status' => $event->fromStatus->value,
                'to_status' => $event->toStatus->value,
            ],
        ));
    }

    public function handlePaymentRecorded(PaymentRecorded $event): void
    {
        $this->recordAction->execute(new AuditLogDTO(
            action: AuditAction::PaymentRecorded,
            auditableType: Payment::class,
            auditableId: $event->payment->id,
            userId: $this->resolveActorId($event->actorId ?? $event->payment->received_by_user_id),
            ipAddress: $this->resolveIp($event->ipAddress),
            metadata: [
                'deal_id' => $event->payment->deal_id,
                'amount' => (float) $event->payment->amount,
                'method' => $event->payment->method->value,
                'paid_at' => $event->payment->paid_at?->toIso8601String(),
            ],
        ));
    }

    /**
     * @return array<class-string, string>
     */
    public function subscribe(Dispatcher $events): array
    {
        return [
            PatientCreated::class => 'handlePatientCreated',
            PatientUpdated::class => 'handlePatientUpdated',
            PatientArchived::class => 'handlePatientArchived',
            DealCreated::class => 'handleDealCreated',
            DealUpdated::class => 'handleDealUpdated',
            DealStageChanged::class => 'handleDealStageChanged',
            AppointmentScheduled::class => 'handleAppointmentScheduled',
            AppointmentStatusUpdated::class => 'handleAppointmentStatusUpdated',
            PaymentRecorded::class => 'handlePaymentRecorded',
        ];
    }

    private function resolveActorId(?int $actorId): ?int
    {
        if ($actorId !== null) {
            return $actorId;
        }

        $authId = auth()->id();

        return is_int($authId) ? $authId : null;
    }

    private function resolveIp(?string $ipAddress): ?string
    {
        if ($ipAddress !== null && $ipAddress !== '') {
            return $ipAddress;
        }

        return request()?->ip();
    }

    /**
     * @param array<string, mixed> $changes
     * @return array<string, mixed>
     */
    private function normalizeChanges(array $changes): array
    {
        $normalized = [];

        foreach ($changes as $key => $value) {
            if ($value instanceof BackedEnum) {
                $normalized[$key] = $value->value;
            } elseif ($value instanceof DateTimeInterface) {
                $normalized[$key] = $value->format(DateTimeInterface::ATOM);
            } else {
                $normalized[$key] = $value;
            }
        }

        return $normalized;
    }
}
