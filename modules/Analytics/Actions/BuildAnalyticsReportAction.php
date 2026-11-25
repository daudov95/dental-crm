<?php

declare(strict_types=1);

namespace Modules\Analytics\Actions;

use Illuminate\Support\Facades\DB;
use Modules\Analytics\DTO\AnalyticsPeriodDTO;
use Modules\Analytics\DTO\AnalyticsSummaryDTO;
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

final readonly class BuildAnalyticsReportAction
{
    public function execute(AnalyticsPeriodDTO $period): AnalyticsSummaryDTO
    {
        $from = $period->from;
        $to = $period->to;

        $sourceCounts = Patient::query()
            ->whereBetween('created_at', [$from, $to])
            ->selectRaw('source, COUNT(*) as cnt')
            ->groupBy('source')
            ->pluck('cnt', 'source');

        $newPatientsCount = (int) $sourceCounts->sum();

        $sources = [];
        foreach (PatientSource::cases() as $source) {
            $count = (int) ($sourceCounts[$source->value] ?? 0);
            $sources[] = [
                'source' => $source->value,
                'label' => $source->label(),
                'count' => $count,
                'share' => $newPatientsCount > 0 ? round(($count / $newPatientsCount) * 100, 1) : 0.0,
            ];
        }
        usort($sources, static fn (array $a, array $b): int => $b['count'] <=> $a['count']);

        $dealStatsByStatus = Deal::query()
            ->whereBetween('created_at', [$from, $to])
            ->selectRaw('status, COUNT(*) as cnt, COALESCE(SUM(total_amount), 0) as total_sum')
            ->groupBy('status')
            ->get()
            ->keyBy(static fn (Deal $row): string => $row->status->value);

        $newDealsCount = (int) $dealStatsByStatus->sum('cnt');
        $convertedDealsCount = (int) (($dealStatsByStatus[DealStatus::InProgress->value]->cnt ?? 0)
            + ($dealStatsByStatus[DealStatus::Completed->value]->cnt ?? 0));
        $conversionRate = $newDealsCount > 0
            ? round(($convertedDealsCount / $newDealsCount) * 100, 1)
            : 0.0;

        $funnel = [];
        foreach (DealStatus::cases() as $status) {
            $row = $dealStatsByStatus[$status->value] ?? null;
            $count = (int) ($row->cnt ?? 0);
            $amount = round((float) ($row->total_sum ?? 0), 2);
            $funnel[] = [
                'status' => $status->value,
                'label' => $status->label(),
                'count' => $count,
                'amount' => $amount,
                'share' => $newDealsCount > 0 ? round(($count / $newDealsCount) * 100, 1) : 0.0,
            ];
        }

        $lossCounts = Deal::query()
            ->whereBetween('created_at', [$from, $to])
            ->where('status', DealStatus::Lost->value)
            ->whereNotNull('loss_reason')
            ->selectRaw('loss_reason, COUNT(*) as cnt')
            ->groupBy('loss_reason')
            ->pluck('cnt', 'loss_reason');

        $totalLost = (int) $lossCounts->sum();
        $lossReasons = [];
        foreach (LossReason::cases() as $reason) {
            $count = (int) ($lossCounts[$reason->value] ?? 0);
            if ($count === 0) {
                continue;
            }
            $lossReasons[] = [
                'reason' => $reason->value,
                'label' => $reason->label(),
                'count' => $count,
                'share' => $totalLost > 0 ? round(($count / $totalLost) * 100, 1) : 0.0,
            ];
        }
        usort($lossReasons, static fn (array $a, array $b): int => $b['count'] <=> $a['count']);

        $paymentStats = Payment::query()
            ->whereBetween('paid_at', [$from, $to])
            ->selectRaw('method, COUNT(*) as cnt, COALESCE(SUM(amount), 0) as total_sum')
            ->groupBy('method')
            ->get()
            ->keyBy(static fn (Payment $row): string => $row->method->value);

        $revenue = round((float) $paymentStats->sum('total_sum'), 2);
        $paymentsCount = (int) $paymentStats->sum('cnt');

        $paymentMethods = [];
        foreach (PaymentMethod::cases() as $method) {
            $row = $paymentStats[$method->value] ?? null;
            $amount = round((float) ($row->total_sum ?? 0), 2);
            $count = (int) ($row->cnt ?? 0);
            $paymentMethods[] = [
                'method' => $method->value,
                'label' => $method->label(),
                'count' => $count,
                'amount' => $amount,
                'share' => $revenue > 0 ? round(($amount / $revenue) * 100, 1) : 0.0,
            ];
        }

        $paidByDeal = DB::table('payments')
            ->selectRaw('deal_id, COALESCE(SUM(amount), 0) as paid_sum')
            ->groupBy('deal_id');

        $totalReceivables = (float) DB::table('deals')
            ->leftJoinSub($paidByDeal, 'p', 'p.deal_id', '=', 'deals.id')
            ->where('deals.status', '!=', DealStatus::Lost->value)
            ->selectRaw('COALESCE(SUM(GREATEST(deals.total_amount - COALESCE(p.paid_sum, 0), 0)), 0) as debt')
            ->value('debt');

        $overdueDealsCount = Deal::query()
            ->where('is_installment', true)
            ->where('is_overdue', true)
            ->where('status', '!=', DealStatus::Lost->value)
            ->count();

        $visitCounts = Appointment::query()
            ->whereBetween('starts_at', [$from, $to])
            ->selectRaw('status, COUNT(*) as cnt')
            ->groupBy('status')
            ->pluck('cnt', 'status');

        $completedVisitsCount = (int) ($visitCounts[AppointmentStatus::Completed->value] ?? 0);
        $noShowVisitsCount = (int) ($visitCounts[AppointmentStatus::NoShow->value] ?? 0);
        $attendanceDenominator = $completedVisitsCount + $noShowVisitsCount;
        $noShowRate = $attendanceDenominator > 0
            ? round(($noShowVisitsCount / $attendanceDenominator) * 100, 1)
            : 0.0;

        $doctorUsers = User::query()
            ->where('role', UserRole::Doctor->value)
            ->orderBy('name')
            ->get(['id', 'name', 'specialization']);

        $visitsByDoctor = Appointment::query()
            ->whereBetween('starts_at', [$from, $to])
            ->whereIn('status', [AppointmentStatus::Completed->value, AppointmentStatus::NoShow->value])
            ->selectRaw("doctor_id, SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed_cnt, SUM(CASE WHEN status = 'no_show' THEN 1 ELSE 0 END) as no_show_cnt")
            ->groupBy('doctor_id')
            ->get()
            ->keyBy('doctor_id');

        $dealsByDoctor = Deal::query()
            ->whereBetween('created_at', [$from, $to])
            ->whereNotNull('doctor_id')
            ->where('status', '!=', DealStatus::Lost->value)
            ->selectRaw('doctor_id, COUNT(*) as deals_cnt, COALESCE(SUM(total_amount), 0) as deals_sum')
            ->groupBy('doctor_id')
            ->get()
            ->keyBy('doctor_id');

        $doctors = [];
        foreach ($doctorUsers as $doctor) {
            $vRow = $visitsByDoctor[$doctor->id] ?? null;
            $dRow = $dealsByDoctor[$doctor->id] ?? null;

            $doctors[] = [
                'doctor_id' => $doctor->id,
                'doctor_name' => $doctor->name,
                'specialization' => $doctor->specialization,
                'completed_visits' => (int) ($vRow->completed_cnt ?? 0),
                'no_show_visits' => (int) ($vRow->no_show_cnt ?? 0),
                'deals_count' => (int) ($dRow->deals_cnt ?? 0),
                'deals_amount' => round((float) ($dRow->deals_sum ?? 0), 2),
            ];
        }

        return new AnalyticsSummaryDTO(
            period: $period,
            newPatientsCount: $newPatientsCount,
            newDealsCount: $newDealsCount,
            convertedDealsCount: $convertedDealsCount,
            conversionRate: $conversionRate,
            revenue: $revenue,
            paymentsCount: $paymentsCount,
            totalReceivables: round($totalReceivables, 2),
            overdueDealsCount: $overdueDealsCount,
            completedVisitsCount: $completedVisitsCount,
            noShowVisitsCount: $noShowVisitsCount,
            noShowRate: $noShowRate,
            funnel: $funnel,
            sources: $sources,
            lossReasons: $lossReasons,
            paymentMethods: $paymentMethods,
            doctors: $doctors,
        );
    }
}
