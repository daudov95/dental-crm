<?php

declare(strict_types=1);

namespace Modules\Analytics\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;
use Modules\Analytics\Actions\BuildAnalyticsReportAction;
use Modules\Analytics\Actions\ExportAnalyticsCsvAction;
use Modules\Analytics\DTO\AnalyticsPeriodDTO;

final readonly class AnalyticsExportController
{
    public function __construct(
        private BuildAnalyticsReportAction $reportAction,
        private ExportAnalyticsCsvAction $exportAction,
    ) {}

    public function __invoke(Request $request): Response
    {
        Gate::authorize('analytics.view');

        $dateFrom = (string) $request->query('from', now()->startOfMonth()->toDateString());
        $dateTo = (string) $request->query('to', now()->toDateString());

        $period = AnalyticsPeriodDTO::fromStrings($dateFrom, $dateTo);
        $summary = $this->reportAction->execute($period);
        $csv = $this->exportAction->execute($summary);

        $filename = sprintf(
            'dental-crm-analytics-%s_%s.csv',
            $period->from->format('Y-m-d'),
            $period->to->format('Y-m-d'),
        );

        return response($csv, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }
}
