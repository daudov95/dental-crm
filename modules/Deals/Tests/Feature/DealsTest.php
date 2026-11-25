<?php

declare(strict_types=1);

namespace Modules\Deals\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Livewire\Livewire;
use Modules\Catalog\Enums\ServiceCategory;
use Modules\Catalog\Models\Service;
use Modules\Deals\Enums\DealStatus;
use Modules\Deals\Enums\LossReason;
use Modules\Deals\Events\DealCreated;
use Modules\Deals\Events\DealStageChanged;
use Modules\Deals\Livewire\DealsIndex;
use Modules\Deals\Models\Deal;
use Modules\Identity\Enums\UserRole;
use Modules\Identity\Models\User;
use Modules\Patients\Enums\PatientSource;
use Modules\Patients\Models\Patient;
use Tests\TestCase;

class DealsTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_deal_with_services_and_calculated_total(): void
    {
        Event::fake([DealCreated::class]);

        $admin = User::create([
            'name' => 'Admin Deals',
            'email' => 'admin.deals@test.local',
            'password' => 'password',
            'role' => UserRole::Admin,
            'is_active' => true,
        ]);

        $patient = Patient::create([
            'full_name' => 'Пациент Сделкин',
            'phone' => '+7 (928) 555-11-22',
            'source' => PatientSource::Instagram,
            'consent_given' => true,
        ]);

        $serviceA = Service::create([
            'name' => 'Коронка',
            'category' => ServiceCategory::Prosthetics,
            'price' => 20000,
            'duration_minutes' => 60,
            'is_active' => true,
        ]);

        $serviceB = Service::create([
            'name' => 'КТ',
            'category' => ServiceCategory::Diagnostics,
            'price' => 2500,
            'duration_minutes' => 20,
            'is_active' => true,
        ]);

        Livewire::actingAs($admin)
            ->test(DealsIndex::class)
            ->call('openCreateModal')
            ->set('title', 'Протезирование + КТ')
            ->set('patientId', (string) $patient->id)
            ->set('status', DealStatus::TreatmentPlan->value)
            ->set('isInstallment', true)
            ->set('nextPaymentDate', now()->addDays(7)->toDateString())
            ->set('items', [
                ['service_id' => (string) $serviceA->id, 'quantity' => 2, 'unit_price' => '20000'],
                ['service_id' => (string) $serviceB->id, 'quantity' => 1, 'unit_price' => '2500'],
            ])
            ->call('save')
            ->assertHasNoErrors();

        $deal = Deal::where('title', 'Протезирование + КТ')->firstOrFail();

        $this->assertSame('42500.00', (string) $deal->total_amount);
        $this->assertTrue($deal->is_installment);
        $this->assertCount(2, $deal->items);
        Event::assertDispatched(DealCreated::class);
    }

    public function test_moving_deal_to_lost_requires_loss_reason(): void
    {
        Event::fake([DealStageChanged::class]);

        $admin = User::create([
            'name' => 'Admin Stage',
            'email' => 'admin.stage@test.local',
            'password' => 'password',
            'role' => UserRole::Admin,
            'is_active' => true,
        ]);

        $patient = Patient::create([
            'full_name' => 'Пациент Отказ',
            'phone' => '+7 (928) 555-33-44',
            'source' => PatientSource::TwoGis,
            'consent_given' => true,
        ]);

        $deal = Deal::create([
            'patient_id' => $patient->id,
            'title' => 'Тестовая сделка на отказ',
            'status' => DealStatus::Consultation,
            'total_amount' => 15000,
        ]);

        Livewire::actingAs($admin)
            ->test(DealsIndex::class)
            ->call('moveStage', $deal->id, DealStatus::Lost->value)
            ->assertSet('showLossModal', true)
            ->call('confirmLoss')
            ->assertHasErrors(['pendingLossReason'])
            ->set('pendingLossReason', LossReason::HighPrice->value)
            ->call('confirmLoss')
            ->assertHasNoErrors();

        $deal->refresh();
        $this->assertSame(DealStatus::Lost, $deal->status);
        $this->assertSame(LossReason::HighPrice, $deal->loss_reason);
        Event::assertDispatched(DealStageChanged::class);
    }

    public function test_doctor_sees_only_assigned_deals_and_cannot_create_or_move(): void
    {
        $doctor1 = User::create([
            'name' => 'Doctor One',
            'email' => 'doc1.deals@test.local',
            'password' => 'password',
            'role' => UserRole::Doctor,
            'is_active' => true,
        ]);

        $doctor2 = User::create([
            'name' => 'Doctor Two',
            'email' => 'doc2.deals@test.local',
            'password' => 'password',
            'role' => UserRole::Doctor,
            'is_active' => true,
        ]);

        $patient = Patient::create([
            'full_name' => 'Общий Пациент',
            'phone' => '+7 (928) 555-77-88',
            'source' => PatientSource::Phone,
            'consent_given' => true,
        ]);

        $ownDeal = Deal::create([
            'patient_id' => $patient->id,
            'doctor_id' => $doctor1->id,
            'title' => 'Своя сделка врача',
            'status' => DealStatus::InProgress,
            'total_amount' => 50000,
        ]);

        $otherDeal = Deal::create([
            'patient_id' => $patient->id,
            'doctor_id' => $doctor2->id,
            'title' => 'Чужая сделка врача',
            'status' => DealStatus::InProgress,
            'total_amount' => 30000,
        ]);

        $this->actingAs($doctor1)
            ->get('/deals')
            ->assertOk()
            ->assertSee('Своя сделка врача')
            ->assertDontSee('Чужая сделка врача');

        $this->actingAs($doctor1)->get('/deals/' . $ownDeal->id)->assertOk();
        $this->actingAs($doctor1)->get('/deals/' . $otherDeal->id)->assertForbidden();

        Livewire::actingAs($doctor1)
            ->test(DealsIndex::class)
            ->call('openCreateModal')
            ->assertForbidden();

        Livewire::actingAs($doctor1)
            ->test(DealsIndex::class)
            ->call('moveStage', $ownDeal->id, DealStatus::Completed->value)
            ->assertForbidden();
    }

    public function test_stage_group_filter_separates_active_and_closed_deals(): void
    {
        $admin = User::create([
            'name' => 'Admin Filter',
            'email' => 'admin.filter@test.local',
            'password' => 'password',
            'role' => UserRole::Admin,
            'is_active' => true,
        ]);

        $patient = Patient::create([
            'full_name' => 'Пациент Фильтр',
            'phone' => '+7 (928) 555-99-00',
            'source' => PatientSource::Instagram,
            'consent_given' => true,
        ]);

        Deal::create([
            'patient_id' => $patient->id,
            'title' => 'Активная ортодонтия',
            'status' => DealStatus::InProgress,
            'total_amount' => 95000,
        ]);

        Deal::create([
            'patient_id' => $patient->id,
            'title' => 'Завершенное лечение',
            'status' => DealStatus::Completed,
            'total_amount' => 12000,
        ]);

        Livewire::actingAs($admin)
            ->test(DealsIndex::class)
            ->assertSee('Активная ортодонтия')
            ->assertSee('Завершенное лечение')
            ->set('stageGroupFilter', 'active')
            ->assertSee('Активная ортодонтия')
            ->assertDontSee('Завершенное лечение')
            ->set('stageGroupFilter', 'closed')
            ->assertSee('Завершенное лечение')
            ->assertDontSee('Активная ортодонтия');
    }

    public function test_admin_can_reorder_deals_within_column_and_across_columns(): void
    {
        $admin = User::create([
            'name' => 'Admin Sort',
            'email' => 'admin.sort@test.local',
            'password' => 'password',
            'role' => UserRole::Admin,
            'is_active' => true,
        ]);

        $patient = Patient::create([
            'full_name' => 'Пациент Сортировка',
            'phone' => '+7 (928) 555-66-77',
            'source' => PatientSource::Phone,
            'consent_given' => true,
        ]);

        $deal1 = Deal::create([
            'patient_id' => $patient->id,
            'title' => 'Первая сделка',
            'status' => DealStatus::New,
            'sort_order' => 1,
            'total_amount' => 10000,
        ]);

        $deal2 = Deal::create([
            'patient_id' => $patient->id,
            'title' => 'Вторая сделка',
            'status' => DealStatus::New,
            'sort_order' => 2,
            'total_amount' => 20000,
        ]);

        Livewire::actingAs($admin)
            ->test(DealsIndex::class)
            ->call('syncColumnOrder', DealStatus::New->value, [$deal2->id, $deal1->id], $deal2->id)
            ->assertHasNoErrors();

        $this->assertSame(1, $deal2->fresh()->sort_order);
        $this->assertSame(2, $deal1->fresh()->sort_order);

        Livewire::actingAs($admin)
            ->test(DealsIndex::class)
            ->call('syncColumnOrder', DealStatus::Consultation->value, [$deal1->id], $deal1->id)
            ->assertHasNoErrors();

        $this->assertSame(DealStatus::Consultation, $deal1->fresh()->status);
        $this->assertSame(1, $deal1->fresh()->sort_order);
    }
}
