<?php

declare(strict_types=1);

namespace Modules\Audit\Tests\Feature;

use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Modules\Audit\Enums\AuditAction;
use Modules\Audit\Livewire\AuditLogIndex;
use Modules\Audit\Models\AuditLog;
use Modules\Billing\Actions\RecordPaymentAction;
use Modules\Billing\DTO\PaymentDTO;
use Modules\Billing\Enums\PaymentMethod;
use Modules\Billing\Models\Payment;
use Modules\Deals\Actions\CreateDealAction;
use Modules\Deals\DTO\DealDTO;
use Modules\Deals\Enums\DealStatus;
use Modules\Deals\Events\DealStageChanged;
use Modules\Deals\Models\Deal;
use Modules\Identity\Enums\UserRole;
use Modules\Identity\Models\User;
use Modules\Patients\Actions\CreatePatientAction;
use Modules\Patients\DTO\PatientDTO;
use Modules\Patients\Enums\PatientSource;
use Modules\Patients\Models\Patient;
use Modules\Scheduling\Enums\AppointmentStatus;
use Modules\Scheduling\Events\AppointmentScheduled;
use Modules\Scheduling\Events\AppointmentStatusUpdated;
use Modules\Scheduling\Models\Appointment;
use Tests\TestCase;

final class AuditTest extends TestCase
{
    use RefreshDatabase;

    public function test_only_director_can_access_audit_log_page(): void
    {
        $director = User::create([
            'name' => 'Audit Director',
            'email' => 'director.audit@test.local',
            'password' => 'password',
            'role' => UserRole::Director,
            'is_active' => true,
        ]);

        $admin = User::create([
            'name' => 'Audit Admin',
            'email' => 'admin.audit@test.local',
            'password' => 'password',
            'role' => UserRole::Admin,
            'is_active' => true,
        ]);

        $doctor = User::create([
            'name' => 'Audit Doctor',
            'email' => 'doctor.audit@test.local',
            'password' => 'password',
            'role' => UserRole::Doctor,
            'is_active' => true,
        ]);

        $this->actingAs($director)
            ->get('/audit')
            ->assertOk()
            ->assertSee('Журнал аудита (152-ФЗ)');

        $this->actingAs($admin)->get('/audit')->assertForbidden();
        $this->actingAs($doctor)->get('/audit')->assertForbidden();
    }

    public function test_domain_events_automatically_write_to_audit_logs(): void
    {
        $admin = User::create([
            'name' => 'Audit Actor',
            'email' => 'actor.audit@test.local',
            'password' => 'password',
            'role' => UserRole::Admin,
            'is_active' => true,
        ]);

        $doctor = User::create([
            'name' => 'Audit Doctor',
            'email' => 'doc.audit@test.local',
            'password' => 'password',
            'role' => UserRole::Doctor,
            'is_active' => true,
        ]);

        // 1. Patients -> PatientCreated
        $patient = app(CreatePatientAction::class)->execute(
            new PatientDTO(
                fullName: 'Тестов Аудит Пациент',
                phone: '+7 (928) 555-00-11',
                birthDate: '1995-05-10',
                source: PatientSource::Instagram,
                notes: null,
                consentGiven: true,
            ),
            actorId: $admin->id,
            ipAddress: '10.0.0.15',
        );

        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $admin->id,
            'action' => AuditAction::PatientCreated->value,
            'auditable_type' => Patient::class,
            'auditable_id' => $patient->id,
            'ip_address' => '10.0.0.15',
        ]);

        // 2. Deals -> DealCreated + DealStageChanged
        $deal = app(CreateDealAction::class)->execute(
            new DealDTO(
                patientId: $patient->id,
                doctorId: $doctor->id,
                title: 'Установка брекетов (аудит)',
                status: DealStatus::New,
                lossReason: null,
                isInstallment: true,
                nextPaymentDate: now()->addMonth()->toDateString(),
                items: [],
            ),
            actorId: $admin->id,
            ipAddress: '10.0.0.16',
        );

        DealStageChanged::dispatch($deal, DealStatus::New, DealStatus::Consultation, null, $admin->id, '10.0.0.16');

        $this->assertDatabaseHas('audit_logs', [
            'action' => AuditAction::DealCreated->value,
            'auditable_type' => Deal::class,
            'auditable_id' => $deal->id,
        ]);

        $this->assertDatabaseHas('audit_logs', [
            'action' => AuditAction::DealStageChanged->value,
            'auditable_type' => Deal::class,
            'auditable_id' => $deal->id,
        ]);

        // 3. Scheduling -> AppointmentScheduled + AppointmentStatusUpdated
        $appointment = Appointment::create([
            'patient_id' => $patient->id,
            'doctor_id' => $doctor->id,
            'deal_id' => $deal->id,
            'starts_at' => CarbonImmutable::now()->addDay()->setTime(10, 0),
            'ends_at' => CarbonImmutable::now()->addDay()->setTime(10, 30),
            'status' => AppointmentStatus::Scheduled,
        ]);

        AppointmentScheduled::dispatch($appointment, $admin->id, '10.0.0.17');
        AppointmentStatusUpdated::dispatch($appointment, AppointmentStatus::Scheduled, AppointmentStatus::Completed, $doctor->id, '10.0.0.18');

        $this->assertDatabaseHas('audit_logs', [
            'action' => AuditAction::AppointmentScheduled->value,
            'auditable_type' => Appointment::class,
            'auditable_id' => $appointment->id,
        ]);

        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $doctor->id,
            'action' => AuditAction::AppointmentStatusUpdated->value,
            'auditable_type' => Appointment::class,
            'auditable_id' => $appointment->id,
        ]);

        // 4. Billing -> PaymentRecorded
        $payment = app(RecordPaymentAction::class)->execute(
            new PaymentDTO(
                dealId: $deal->id,
                receivedByUserId: $admin->id,
                amount: 15000.0,
                method: PaymentMethod::Card,
                paidAt: CarbonImmutable::now(),
                comment: 'Тестовая оплата',
            ),
            actorId: $admin->id,
            ipAddress: '10.0.0.19',
        );

        $this->assertDatabaseHas('audit_logs', [
            'action' => AuditAction::PaymentRecorded->value,
            'auditable_type' => Payment::class,
            'auditable_id' => $payment->id,
            'ip_address' => '10.0.0.19',
        ]);
    }

    public function test_director_can_filter_and_inspect_audit_logs(): void
    {
        $director = User::create([
            'name' => 'Filter Director',
            'email' => 'filter.director@test.local',
            'password' => 'password',
            'role' => UserRole::Director,
            'is_active' => true,
        ]);

        $patientLog = AuditLog::create([
            'user_id' => $director->id,
            'action' => AuditAction::PatientCreated,
            'auditable_type' => Patient::class,
            'auditable_id' => 101,
            'ip_address' => '192.168.1.10',
            'metadata' => ['full_name' => 'Иванов Иван'],
        ]);

        AuditLog::create([
            'user_id' => $director->id,
            'action' => AuditAction::PaymentRecorded,
            'auditable_type' => Payment::class,
            'auditable_id' => 202,
            'ip_address' => '192.168.1.20',
            'metadata' => ['amount' => 25000],
        ]);

        Livewire::actingAs($director)
            ->test(AuditLogIndex::class)
            ->set('actionFilter', AuditAction::PatientCreated->value)
            ->assertSee('192.168.1.10')
            ->assertDontSee('192.168.1.20')
            ->call('inspectLog', $patientLog->id)
            ->assertSet('selectedLogId', $patientLog->id)
            ->assertSee('Иванов Иван')
            ->assertSee('ФИО пациента')
            ->assertSee('Иванов Иван');
    }
}
