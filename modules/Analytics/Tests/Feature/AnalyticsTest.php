<?php

declare(strict_types=1);

namespace Modules\Analytics\Tests\Feature;

use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Analytics\Actions\BuildAnalyticsReportAction;
use Modules\Analytics\DTO\AnalyticsPeriodDTO;
use Modules\Billing\Enums\PaymentMethod;
use Modules\Billing\Models\Payment;
use Modules\Deals\Enums\DealStatus;
use Modules\Deals\Enums\LossReason;
use Modules\Deals\Models\Deal;
use Modules\Identity\Enums\UserRole;
use Modules\Identity\Models\User;
use Modules\Patients\Enums\PatientSource;
use Modules\Patients\Models\Patient;
use Modules\Scheduling\Enums\AppointmentStatus;
use Modules\Scheduling\Models\Appointment;
use Tests\TestCase;

final class AnalyticsTest extends TestCase
{
    use RefreshDatabase;

    public function test_only_director_can_access_analytics_and_export(): void
    {
        $director = User::create([
            'name' => 'Analytics Director',
            'email' => 'director.analytics@test.local',
            'password' => 'password',
            'role' => UserRole::Director,
            'is_active' => true,
        ]);

        $admin = User::create([
            'name' => 'Analytics Admin',
            'email' => 'admin.analytics@test.local',
            'password' => 'password',
            'role' => UserRole::Admin,
            'is_active' => true,
        ]);

        $doctor = User::create([
            'name' => 'Analytics Doctor',
            'email' => 'doctor.analytics@test.local',
            'password' => 'password',
            'role' => UserRole::Doctor,
            'is_active' => true,
        ]);

        $this->actingAs($director)->get('/analytics')->assertOk()->assertSee('Аналитика и KPI руководителя');
        $this->actingAs($director)->get('/analytics/export')->assertOk();

        $this->actingAs($admin)->get('/analytics')->assertForbidden();
        $this->actingAs($admin)->get('/analytics/export')->assertForbidden();

        $this->actingAs($doctor)->get('/analytics')->assertForbidden();
        $this->actingAs($doctor)->get('/analytics/export')->assertForbidden();
    }

    public function test_analytics_report_calculates_kpis_accurately_and_exports_csv(): void
    {
        $director = User::create([
            'name' => 'KPI Director',
            'email' => 'kpi.director@test.local',
            'password' => 'password',
            'role' => UserRole::Director,
            'is_active' => true,
        ]);

        $doctor = User::create([
            'name' => 'KPI Doctor',
            'email' => 'kpi.doctor@test.local',
            'password' => 'password',
            'role' => UserRole::Doctor,
            'specialization' => 'Ортодонт',
            'is_active' => true,
        ]);

        $p1 = Patient::create([
            'full_name' => 'Пациент 1',
            'phone' => '+7 (928) 100-00-01',
            'source' => PatientSource::Instagram,
            'consent_given' => true,
        ]);

        $p2 = Patient::create([
            'full_name' => 'Пациент 2',
            'phone' => '+7 (928) 100-00-02',
            'source' => PatientSource::WhatsApp,
            'consent_given' => true,
        ]);

        $activeDeal = Deal::create([
            'patient_id' => $p1->id,
            'doctor_id' => $doctor->id,
            'title' => 'Брекеты (в работе)',
            'status' => DealStatus::InProgress,
            'total_amount' => 100000,
            'is_installment' => true,
            'is_overdue' => true,
        ]);

        Deal::create([
            'patient_id' => $p2->id,
            'doctor_id' => $doctor->id,
            'title' => 'Имплант (отказ)',
            'status' => DealStatus::Lost,
            'loss_reason' => LossReason::HighPrice,
            'total_amount' => 50000,
            'is_installment' => false,
            'is_overdue' => false,
        ]);

        Payment::create([
            'deal_id' => $activeDeal->id,
            'received_by_user_id' => $director->id,
            'amount' => 40000,
            'method' => PaymentMethod::Card,
            'paid_at' => CarbonImmutable::now(),
        ]);

        Appointment::create([
            'patient_id' => $p1->id,
            'doctor_id' => $doctor->id,
            'deal_id' => $activeDeal->id,
            'starts_at' => CarbonImmutable::now()->setTime(10, 0),
            'ends_at' => CarbonImmutable::now()->setTime(10, 30),
            'status' => AppointmentStatus::Completed,
        ]);

        Appointment::create([
            'patient_id' => $p2->id,
            'doctor_id' => $doctor->id,
            'starts_at' => CarbonImmutable::now()->setTime(11, 0),
            'ends_at' => CarbonImmutable::now()->setTime(11, 30),
            'status' => AppointmentStatus::NoShow,
        ]);

        $period = AnalyticsPeriodDTO::fromStrings(
            now()->startOfMonth()->toDateString(),
            now()->endOfMonth()->toDateString(),
        );

        $summary = app(BuildAnalyticsReportAction::class)->execute($period);

        $this->assertSame(2, $summary->newPatientsCount);
        $this->assertSame(2, $summary->newDealsCount);
        $this->assertSame(1, $summary->convertedDealsCount);
        $this->assertSame(50.0, $summary->conversionRate);
        $this->assertSame(40000.0, $summary->revenue);
        $this->assertSame(60000.0, $summary->totalReceivables);
        $this->assertSame(1, $summary->overdueDealsCount);
        $this->assertSame(50.0, $summary->noShowRate);

        $response = $this->actingAs($director)->get('/analytics/export');
        $response->assertOk();
        $response->assertHeader('Content-Type', 'text/csv; charset=UTF-8');
        $this->assertStringContainsString('КЛЮЧЕВЫЕ ПОКАЗАТЕЛИ (KPI)', $response->getContent());
        $this->assertStringContainsString('40 000,00', $response->getContent());
    }
}
