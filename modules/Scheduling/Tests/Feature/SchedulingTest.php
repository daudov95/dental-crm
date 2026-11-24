<?php

declare(strict_types=1);

namespace Modules\Scheduling\Tests\Feature;

use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Livewire\Livewire;
use Modules\Deals\Enums\DealStatus;
use Modules\Deals\Models\Deal;
use Modules\Identity\Enums\UserRole;
use Modules\Identity\Models\User;
use Modules\Patients\Enums\PatientSource;
use Modules\Patients\Models\Patient;
use Modules\Scheduling\Enums\AppointmentStatus;
use Modules\Scheduling\Events\AppointmentScheduled;
use Modules\Scheduling\Events\AppointmentStatusUpdated;
use Modules\Scheduling\Livewire\ScheduleIndex;
use Modules\Scheduling\Models\Appointment;
use Tests\TestCase;

final class SchedulingTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_schedule_appointment_and_overlapping_slot_is_rejected(): void
    {
        Event::fake([AppointmentScheduled::class]);

        $admin = User::create([
            'name' => 'Admin Schedule',
            'email' => 'admin.sched@test.local',
            'password' => 'password',
            'role' => UserRole::Admin,
            'is_active' => true,
        ]);

        $doctor = User::create([
            'name' => 'Doctor Ortho',
            'email' => 'doc.sched@test.local',
            'password' => 'password',
            'role' => UserRole::Doctor,
            'is_active' => true,
        ]);

        $patient = Patient::create([
            'full_name' => 'Пациент Запись',
            'phone' => '+7 (928) 123-45-67',
            'source' => PatientSource::Instagram,
            'consent_given' => true,
        ]);

        $deal = Deal::create([
            'patient_id' => $patient->id,
            'doctor_id' => $doctor->id,
            'title' => 'Ортодонтия (брекеты)',
            'status' => DealStatus::InProgress,
            'total_amount' => 95000,
        ]);

        $date = now()->addDays(2)->toDateString();

        // Успешная запись 10:00 - 11:00
        Livewire::actingAs($admin)
            ->test(ScheduleIndex::class)
            ->call('openCreateModal', $date)
            ->set('patientId', (string) $patient->id)
            ->set('doctorId', (string) $doctor->id)
            ->set('dealId', (string) $deal->id)
            ->set('appointmentDate', $date)
            ->set('startTime', '10:00')
            ->set('durationMinutes', 60)
            ->call('save')
            ->assertHasNoErrors();

        Event::assertDispatched(AppointmentScheduled::class);
        $this->assertDatabaseCount('appointments', 1);

        // Попытка пересечения 10:30 - 11:00 к тому же врачу должна быть отклонена
        Livewire::actingAs($admin)
            ->test(ScheduleIndex::class)
            ->call('openCreateModal', $date)
            ->set('patientId', (string) $patient->id)
            ->set('doctorId', (string) $doctor->id)
            ->set('appointmentDate', $date)
            ->set('startTime', '10:30')
            ->set('durationMinutes', 30)
            ->call('save')
            ->assertHasErrors(['startTime']);

        $this->assertDatabaseCount('appointments', 1);

        // Смежный слот 11:00 - 11:30 без пересечения должен пройти
        Livewire::actingAs($admin)
            ->test(ScheduleIndex::class)
            ->call('openCreateModal', $date)
            ->set('patientId', (string) $patient->id)
            ->set('doctorId', (string) $doctor->id)
            ->set('appointmentDate', $date)
            ->set('startTime', '11:00')
            ->set('durationMinutes', 30)
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseCount('appointments', 2);
    }

    public function test_doctor_sees_only_own_appointments_and_can_update_own_status(): void
    {
        Event::fake([AppointmentStatusUpdated::class]);

        $doctor1 = User::create([
            'name' => 'Doctor One',
            'email' => 'doc1.sched@test.local',
            'password' => 'password',
            'role' => UserRole::Doctor,
            'is_active' => true,
        ]);

        $doctor2 = User::create([
            'name' => 'Doctor Two',
            'email' => 'doc2.sched@test.local',
            'password' => 'password',
            'role' => UserRole::Doctor,
            'is_active' => true,
        ]);

        $patient1 = Patient::create([
            'full_name' => 'Свой Пациент Врача',
            'phone' => '+7 (928) 222-33-44',
            'source' => PatientSource::Phone,
            'consent_given' => true,
        ]);

        $patient2 = Patient::create([
            'full_name' => 'Чужой Пациент Врача',
            'phone' => '+7 (928) 333-44-55',
            'source' => PatientSource::Phone,
            'consent_given' => true,
        ]);

        $ownVisit = Appointment::create([
            'patient_id' => $patient1->id,
            'doctor_id' => $doctor1->id,
            'starts_at' => CarbonImmutable::today()->setTime(10, 0),
            'ends_at' => CarbonImmutable::today()->setTime(10, 30),
            'status' => AppointmentStatus::Scheduled,
        ]);

        $otherVisit = Appointment::create([
            'patient_id' => $patient2->id,
            'doctor_id' => $doctor2->id,
            'starts_at' => CarbonImmutable::today()->setTime(12, 0),
            'ends_at' => CarbonImmutable::today()->setTime(12, 30),
            'status' => AppointmentStatus::Scheduled,
        ]);

        $this->actingAs($doctor1)
            ->get('/schedule')
            ->assertOk()
            ->assertSee('Свой Пациент Врача')
            ->assertDontSee('Чужой Пациент Врача');

        Livewire::actingAs($doctor1)
            ->test(ScheduleIndex::class)
            ->call('openCreateModal')
            ->assertForbidden();

        Livewire::actingAs($doctor1)
            ->test(ScheduleIndex::class)
            ->call('openStatusModal', $otherVisit->id)
            ->assertForbidden();

        Livewire::actingAs($doctor1)
            ->test(ScheduleIndex::class)
            ->call('openStatusModal', $ownVisit->id)
            ->set('quickStatus', AppointmentStatus::Completed->value)
            ->set('quickNotes', 'Замена дуги выполнена')
            ->call('saveQuickStatus')
            ->assertHasNoErrors();

        $ownVisit->refresh();
        $this->assertSame(AppointmentStatus::Completed, $ownVisit->status);
        $this->assertSame('Замена дуги выполнена', $ownVisit->notes);
        Event::assertDispatched(AppointmentStatusUpdated::class);
    }

    public function test_tomorrow_reminders_tab_shows_only_tomorrows_scheduled_visits(): void
    {
        $admin = User::create([
            'name' => 'Admin Reminders',
            'email' => 'admin.rem@test.local',
            'password' => 'password',
            'role' => UserRole::Admin,
            'is_active' => true,
        ]);

        $doctor = User::create([
            'name' => 'Doctor Reminders',
            'email' => 'doc.rem@test.local',
            'password' => 'password',
            'role' => UserRole::Doctor,
            'is_active' => true,
        ]);

        $patientTomorrow = Patient::create([
            'full_name' => 'Пациент На Завтра',
            'phone' => '+7 (928) 777-11-11',
            'source' => PatientSource::WhatsApp,
            'consent_given' => true,
        ]);

        $patientToday = Patient::create([
            'full_name' => 'Пациент На Сегодня',
            'phone' => '+7 (928) 777-22-22',
            'source' => PatientSource::WhatsApp,
            'consent_given' => true,
        ]);

        Appointment::create([
            'patient_id' => $patientTomorrow->id,
            'doctor_id' => $doctor->id,
            'starts_at' => CarbonImmutable::tomorrow()->setTime(10, 0),
            'ends_at' => CarbonImmutable::tomorrow()->setTime(10, 30),
            'status' => AppointmentStatus::Scheduled,
        ]);

        Appointment::create([
            'patient_id' => $patientToday->id,
            'doctor_id' => $doctor->id,
            'starts_at' => CarbonImmutable::today()->setTime(15, 0),
            'ends_at' => CarbonImmutable::today()->setTime(15, 30),
            'status' => AppointmentStatus::Scheduled,
        ]);

        Livewire::actingAs($admin)
            ->test(ScheduleIndex::class)
            ->call('setViewMode', 'tomorrow')
            ->assertSee('Пациент На Завтра')
            ->assertDontSee('Пациент На Сегодня');
    }
}
