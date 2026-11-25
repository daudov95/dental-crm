<?php

declare(strict_types=1);

namespace Modules\Patients\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Livewire\Livewire;
use Modules\Deals\Enums\DealStatus;
use Modules\Deals\Models\Deal;
use Modules\Identity\Enums\UserRole;
use Modules\Identity\Models\User;
use Modules\Patients\Enums\PatientSource;
use Modules\Patients\Events\PatientArchived;
use Modules\Patients\Events\PatientCreated;
use Modules\Patients\Livewire\PatientIndex;
use Modules\Patients\Models\Patient;
use Tests\TestCase;

class PatientsTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_archive_and_restore_patient(): void
    {
        Event::fake([PatientCreated::class, PatientArchived::class]);

        $admin = User::create([
            'name' => 'Test Admin',
            'email' => 'admin.patients@test.local',
            'password' => 'password',
            'role' => UserRole::Admin,
            'is_active' => true,
        ]);

        Livewire::actingAs($admin)
            ->test(PatientIndex::class)
            ->call('openCreateModal')
            ->set('fullName', 'Тестов Ахмед Бесланович')
            ->set('phone', '+7 (777) 111-22-33')
            ->set('source', PatientSource::Instagram->value)
            ->set('consentGiven', true)
            ->call('save')
            ->assertHasNoErrors();

        $patient = Patient::where('phone', '+7 (777) 111-22-33')->firstOrFail();
        Event::assertDispatched(PatientCreated::class);

        Livewire::actingAs($admin)
            ->test(PatientIndex::class)
            ->call('archive', $patient->id);

        $this->assertSoftDeleted('patients', ['id' => $patient->id]);
        Event::assertDispatched(PatientArchived::class);

        Livewire::actingAs($admin)
            ->test(PatientIndex::class)
            ->call('restore', $patient->id);

        $this->assertNotSoftDeleted('patients', ['id' => $patient->id]);
    }

    public function test_doctor_can_view_patients_and_card_but_cannot_create(): void
    {
        $doctor = User::create([
            'name' => 'Test Doctor',
            'email' => 'doctor.patients@test.local',
            'password' => 'password',
            'role' => UserRole::Doctor,
            'is_active' => true,
        ]);

        $patient = Patient::create([
            'full_name' => 'Пациент Для Просмотра',
            'phone' => '+7 (928) 999-88-77',
            'source' => PatientSource::WhatsApp,
            'consent_given' => true,
        ]);

        Deal::create([
            'patient_id' => $patient->id,
            'doctor_id' => $doctor->id,
            'title' => 'Консультация для теста',
            'status' => DealStatus::Consultation,
            'total_amount' => 0,
            'is_installment' => false,
            'is_overdue' => false,
        ]);

        $this->actingAs($doctor)
            ->get('/patients')
            ->assertOk()
            ->assertSee('Пациент Для Просмотра');

        $this->actingAs($doctor)
            ->get('/patients/' . $patient->id)
            ->assertOk()
            ->assertSee('Пациент Для Просмотра');

        Livewire::actingAs($doctor)
            ->test(PatientIndex::class)
            ->call('openCreateModal')
            ->assertForbidden();
    }
}
