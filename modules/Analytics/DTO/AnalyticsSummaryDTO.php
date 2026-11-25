<?php

declare(strict_types=1);

namespace Modules\Analytics\DTO;

final readonly class AnalyticsSummaryDTO
{
    /**
     * @param list<array{status: string, label: string, count: int, amount: float, share: float}> $funnel
     * @param list<array{source: string, label: string, count: int, share: float}> $sources
     * @param list<array{reason: string, label: string, count: int, share: float}> $lossReasons
     * @param list<array{method: string, label: string, count: int, amount: float, share: float}> $paymentMethods
     * @param list<array{doctor_id: int, doctor_name: string, specialization: ?string, completed_visits: int, no_show_visits: int, deals_count: int, deals_amount: float}> $doctors
     */
    public function __construct(
        public AnalyticsPeriodDTO $period,
        public int $newPatientsCount,
        public int $newDealsCount,
        public int $convertedDealsCount,
        public float $conversionRate,
        public float $revenue,
        public int $paymentsCount,
        public float $totalReceivables,
        public int $overdueDealsCount,
        public int $completedVisitsCount,
        public int $noShowVisitsCount,
        public float $noShowRate,
        public array $funnel,
        public array $sources,
        public array $lossReasons,
        public array $paymentMethods,
        public array $doctors,
    ) {}
}
