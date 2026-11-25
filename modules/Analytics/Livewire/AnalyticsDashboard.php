<?php

declare(strict_types=1);

namespace Modules\Analytics\Livewire;

use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;
use Modules\Analytics\Actions\BuildAnalyticsReportAction;
use Modules\Analytics\DTO\AnalyticsPeriodDTO;

final class AnalyticsDashboard extends Component
{
    public string $dateFrom = '';
    public string $dateTo = '';

    public function mount(): void
    {
        Gate::authorize('analytics.view');

        $this->dateFrom = now()->startOfMonth()->toDateString();
        $this->dateTo = now()->toDateString();
    }

    public function setPreset(string $preset): void
    {
        [$this->dateFrom, $this->dateTo] = match ($preset) {
            'today' => [now()->toDateString(), now()->toDateString()],
            'week' => [now()->subDays(6)->toDateString(), now()->toDateString()],
            'month' => [now()->startOfMonth()->toDateString(), now()->toDateString()],
            'quarter' => [now()->subMonths(3)->startOfDay()->toDateString(), now()->toDateString()],
            'year' => [now()->startOfYear()->toDateString(), now()->toDateString()],
            default => [now()->startOfMonth()->toDateString(), now()->toDateString()],
        };
    }

    public function render(BuildAnalyticsReportAction $reportAction): View
    {
        $period = AnalyticsPeriodDTO::fromStrings(
            $this->dateFrom !== '' ? $this->dateFrom : now()->startOfMonth()->toDateString(),
            $this->dateTo !== '' ? $this->dateTo : now()->toDateString(),
        );

        $summary = $reportAction->execute($period);

        return view('analytics::livewire.analytics-dashboard', [
            'summary' => $summary,
        ])->layout('layouts.app', ['title' => 'Аналитика и KPI']);
    }
}
