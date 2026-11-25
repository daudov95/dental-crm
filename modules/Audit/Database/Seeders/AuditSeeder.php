<?php

declare(strict_types=1);

namespace Modules\Audit\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Audit\Enums\AuditAction;
use Modules\Audit\Models\AuditLog;
use Modules\Billing\Models\Payment;
use Modules\Deals\Models\Deal;
use Modules\Identity\Enums\UserRole;
use Modules\Identity\Models\User;
use Modules\Patients\Models\Patient;
use Modules\Scheduling\Models\Appointment;

final class AuditSeeder extends Seeder
{
    public function run(): void
    {
        if (AuditLog::exists()) {
            return;
        }

        $admin = User::where('role', UserRole::Admin)->first()
            ?? User::where('role', UserRole::Director)->first();

        $patient = Patient::first();
        $deal = Deal::first();
        $appointment = Appointment::first();
        $payment = Payment::first();

        if ($patient) {
            AuditLog::create([
                'user_id' => $admin?->id,
                'action' => AuditAction::PatientCreated,
                'auditable_type' => Patient::class,
                'auditable_id' => $patient->id,
                'ip_address' => '127.0.0.1',
                'metadata' => [
                    'full_name' => $patient->full_name,
                    'phone' => $patient->phone,
                    'source' => $patient->source->value,
                    'consent_given' => $patient->consent_given,
                ],
                'created_at' => now()->subHours(6),
            ]);
        }

        if ($deal) {
            AuditLog::create([
                'user_id' => $admin?->id,
                'action' => AuditAction::DealStageChanged,
                'auditable_type' => Deal::class,
                'auditable_id' => $deal->id,
                'ip_address' => '127.0.0.1',
                'metadata' => [
                    'title' => $deal->title,
                    'from_status' => 'consultation',
                    'to_status' => $deal->status->value,
                ],
                'created_at' => now()->subHours(4),
            ]);
        }

        if ($appointment) {
            AuditLog::create([
                'user_id' => $admin?->id,
                'action' => AuditAction::AppointmentScheduled,
                'auditable_type' => Appointment::class,
                'auditable_id' => $appointment->id,
                'ip_address' => '127.0.0.1',
                'metadata' => [
                    'patient_id' => $appointment->patient_id,
                    'doctor_id' => $appointment->doctor_id,
                    'starts_at' => $appointment->starts_at?->toIso8601String(),
                ],
                'created_at' => now()->subHours(2),
            ]);
        }

        if ($payment) {
            AuditLog::create([
                'user_id' => $admin?->id,
                'action' => AuditAction::PaymentRecorded,
                'auditable_type' => Payment::class,
                'auditable_id' => $payment->id,
                'ip_address' => '127.0.0.1',
                'metadata' => [
                    'deal_id' => $payment->deal_id,
                    'amount' => (float) $payment->amount,
                    'method' => $payment->method->value,
                ],
                'created_at' => now()->subHour(),
            ]);
        }
    }
}
