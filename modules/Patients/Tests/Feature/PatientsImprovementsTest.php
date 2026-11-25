<?php

declare(strict_types=1);

namespace Modules\Patients\Tests\Feature;

use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Modules\Billing\Enums\PaymentMethod;
use Modules\Billing\Models\Payment;
use Modules\Deals\Enums\DealStatus;
use Modules\Deals\Models\Deal;
use Modules\Identity\Enums\UserRole;
use Modules\Identity\Models\User;
use Modules\Patients\Enums\PatientSource;
use Modules\Patients\Livewire\PatientIndex;
use Modules\Patients\Models\Patient;
use Modules\Scheduling\Enums\AppointmentStatus;
use Modules\Scheduling\Models\Appointment;
use Tests\TestCase;

final class PatientsImprovementsTest extends TestCase
{
    use RefreshDatabase;

    public function test_phone_is_normalized_and_validated_on_save(): void
    {
        $admin = User::create([
            'name' => 'Phone Admin',
            'email' => 'phone.admin@test.local',
            'password' => 'password',
            'role' => UserRole::Admin,
            'is_active' => true,
        ]);

        Livewire::actingAs($admin)
            ->test(PatientIndex::class)
            ->call('openCreateModal')
            ->set('fullName', 'Тестов Формат Телефона')
            ->set('phone', '89287776655')
            ->set('source', PatientSource::Phone->value)
            ->set('consentGiven', true)
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('patients', [
            'full_name' => 'Тестов Формат Телефона',
            'phone' => '+7 (928) 777-66-55',
        ]);

        Livewire::actingAs($admin)
            ->test(PatientIndex::class)
            ->call('openCreateModal')
            ->set('fullName', 'Дубликат Телефона')
            ->set('phone', '+79287776655')
            ->set('source', PatientSource::WhatsApp->value)
            ->call('save')
            ->assertHasErrors(['phone']);

        Livewire::actingAs($admin)
            ->test(PatientIndex::class)
            ->call('openCreateModal')
            ->set('fullName', 'Неверный Телефон')
            ->set('phone', '12345')
            ->set('source', PatientSource::WhatsApp->value)
            ->call('save')
            ->assertHasErrors(['phone']);
    }

    public function test_doctor_sees_only_assigned_patients_and_cannot_see_billing(): void
    {
        $admin = User::create([
            'name' => 'Card Admin',
            'email' => 'card.admin@test.local',
            'password' => 'password',
            'role' => UserRole::Admin,
            'is_active' => true,
        ]);

        $doctor = User::create([
            'name' => 'Doctor One',
            'email' => 'doctor.one@test.local',
            'password' => 'password',
            'role' => UserRole::Doctor,
            'is_active' => true,
        ]);

        $otherDoctor = User::create([
            'name' => 'Doctor Two',
            'email' => 'doctor.two@test.local',
            'password' => 'password',
            'role' => UserRole::Doctor,
            'is_active' => true,
        ]);

        $ownPatient = Patient::create([
            'full_name' => 'Свой Пациент Врача',
            'phone' => '+7 (928) 111-22-33',
            'source' => PatientSource::Instagram,
            'consent_given' => true,
        ]);

        $foreignPatient = Patient::create([
            'full_name' => 'Чужой Пациент Врача',
            'phone' => '+7 (928) 444-55-66',
            'source' => PatientSource::TwoGis,
            'consent_given' => true,
        ]);

        $deal = Deal::create([
            'patient_id' => $ownPatient->id,
            'doctor_id' => $doctor->id,
            'title' => 'Брекеты для своего пациента',
            'status' => DealStatus::InProgress,
            'total_amount' => 120000,
            'is_installment' => true,
            'is_overdue' => false,
        ]);

        Deal::create([
            'patient_id' => $foreignPatient->id,
            'doctor_id' => $otherDoctor->id,
            'title' => 'Чужая сделка',
            'status' => DealStatus::InProgress,
            'total_amount' => 50000,
            'is_installment' => false,
            'is_overdue' => false,
        ]);

        Appointment::create([
            'patient_id' => $ownPatient->id,
            'doctor_id' => $doctor->id,
            'deal_id' => $deal->id,
            'starts_at' => CarbonImmutable::now()->addDay()->setTime(11, 0),
            'ends_at' => CarbonImmutable::now()->addDay()->setTime(11, 45),
            'status' => AppointmentStatus::Scheduled,
            'notes' => 'Плановая коррекция дуги',
        ]);

        Payment::create([
            'deal_id' => $deal->id,
            'received_by_user_id' => $admin->id,
            'amount' => 30000,
            'method' => PaymentMethod::Card,
            'paid_at' => CarbonImmutable::now(),
            'comment' => 'Авансовый платеж',
        ]);

        $this->actingAs($doctor)
            ->get('/patients')
            ->assertOk()
            ->assertSee('Свой Пациент Врача')
            ->assertDontSee('Чужой Пациент Врача');

        $this->actingAs($doctor)
            ->get('/patients/' . $foreignPatient->id)
            ->assertForbidden();

        $this->actingAs($doctor)
            ->get('/patients/' . $ownPatient->id)
            ->assertOk()
            ->assertSee('Брекеты для своего пациента')
            ->assertSee('Плановая коррекция дуги')
            ->assertDontSee('Авансовый платеж');

        $this->actingAs($admin)
            ->get('/patients/' . $ownPatient->id)
            ->assertOk()
            ->assertSee('Брекеты для своего пациента')
            ->assertSee('Плановая коррекция дуги')
            ->assertSee('Авансовый платеж')
            ->assertSee('30 000 ₽')
            ->assertSee('90 000 ₽');
    }
}
