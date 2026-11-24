<?php

declare(strict_types=1);

namespace Modules\Billing\Tests\Feature;

use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Livewire\Livewire;
use Modules\Billing\Actions\CheckOverdueInstallmentsAction;
use Modules\Billing\Actions\GenerateTomorrowRemindersAction;
use Modules\Billing\Enums\PaymentMethod;
use Modules\Billing\Events\PaymentRecorded;
use Modules\Billing\Livewire\BillingIndex;
use Modules\Billing\Models\SystemAlert;
use Modules\Deals\Enums\DealStatus;
use Modules\Deals\Models\Deal;
use Modules\Identity\Enums\UserRole;
use Modules\Identity\Models\User;
use Modules\Patients\Enums\PatientSource;
use Modules\Patients\Models\Patient;
use Modules\Scheduling\Enums\AppointmentStatus;
use Modules\Scheduling\Models\Appointment;
use Tests\TestCase;

final class BillingTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_record_payment_and_clear_overdue_status(): void
    {
        Event::fake([PaymentRecorded::class]);

        $admin = User::create([
            'name' => 'Admin Billing',
            'email' => 'admin.billing@test.local',
            'password' => 'password',
            'role' => UserRole::Admin,
            'is_active' => true,
        ]);

        $patient = Patient::create([
            'full_name' => 'Пациент Оплата',
            'phone' => '+7 (928) 400-11-22',
            'source' => PatientSource::Instagram,
            'consent_given' => true,
        ]);

        $deal = Deal::create([
            'patient_id' => $patient->id,
            'title' => 'Протезирование в рассрочку',
            'status' => DealStatus::InProgress,
            'total_amount' => 40000,
            'is_installment' => true,
            'next_payment_date' => now()->subDays(3)->toDateString(),
            'is_overdue' => true,
        ]);

        SystemAlert::create([
            'type' => 'overdue_installment',
            'title' => 'Просрочка',
            'message' => 'Тест просрочки',
            'entity_type' => Deal::class,
            'entity_id' => $deal->id,
            'is_read' => false,
        ]);

        Livewire::actingAs($admin)
            ->test(BillingIndex::class)
            ->call('openPaymentModal', $deal->id)
            ->set('amount', '40000')
            ->set('method', PaymentMethod::Card->value)
            ->set('paidAt', now()->format('Y-m-d\TH:i'))
            ->set('comment', 'Полное погашение')
            ->call('recordPayment')
            ->assertHasNoErrors();

        Event::assertDispatched(PaymentRecorded::class);
        $this->assertDatabaseHas('payments', [
            'deal_id' => $deal->id,
            'amount' => '40000.00',
        ]);

        $deal->refresh();
        $this->assertFalse($deal->is_overdue);
        $this->assertNull($deal->next_payment_date);
        $this->assertSame(0, SystemAlert::unread()->count());
    }

    public function test_overdue_installments_check_marks_deal_and_creates_alert_without_duplicates(): void
    {
        $patient = Patient::create([
            'full_name' => 'Пациент Должник',
            'phone' => '+7 (928) 400-33-44',
            'source' => PatientSource::WhatsApp,
            'consent_given' => true,
        ]);

        $deal = Deal::create([
            'patient_id' => $patient->id,
            'title' => 'Брекеты рассрочка',
            'status' => DealStatus::InProgress,
            'total_amount' => 95000,
            'is_installment' => true,
            'next_payment_date' => now()->subDay()->toDateString(),
            'is_overdue' => false,
        ]);

        $action = app(CheckOverdueInstallmentsAction::class);

        $firstCount = $action->execute();
        $secondCount = $action->execute();

        $this->assertSame(1, $firstCount);
        $this->assertSame(0, $secondCount);
        $this->assertTrue($deal->fresh()->is_overdue);
        $this->assertDatabaseCount('system_alerts', 1);
    }

    public function test_tomorrow_reminders_action_creates_single_daily_alert(): void
    {
        $doctor = User::create([
            'name' => 'Doctor Alert',
            'email' => 'doc.alert@test.local',
            'password' => 'password',
            'role' => UserRole::Doctor,
            'is_active' => true,
        ]);

        $patient = Patient::create([
            'full_name' => 'Пациент Завтра',
            'phone' => '+7 (928) 400-55-66',
            'source' => PatientSource::Phone,
            'consent_given' => true,
        ]);

        Appointment::create([
            'patient_id' => $patient->id,
            'doctor_id' => $doctor->id,
            'starts_at' => CarbonImmutable::tomorrow()->setTime(11, 0),
            'ends_at' => CarbonImmutable::tomorrow()->setTime(11, 30),
            'status' => AppointmentStatus::Scheduled,
        ]);

        $action = app(GenerateTomorrowRemindersAction::class);

        $this->assertTrue($action->execute());
        $this->assertFalse($action->execute());
        $this->assertDatabaseHas('system_alerts', ['type' => 'tomorrow_reminders']);
    }

    public function test_doctor_cannot_access_billing_page(): void
    {
        $doctor = User::create([
            'name' => 'Doctor Billing',
            'email' => 'doc.billing@test.local',
            'password' => 'password',
            'role' => UserRole::Doctor,
            'is_active' => true,
        ]);

        $this->actingAs($doctor)->get('/billing')->assertForbidden();
    }
}
