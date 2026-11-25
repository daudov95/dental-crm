<?php

declare(strict_types=1);

namespace Modules\Audit\Livewire;

use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Component;
use Livewire\WithPagination;
use Modules\Audit\Enums\AuditAction;
use Modules\Audit\Models\AuditLog;
use Modules\Billing\Models\Payment;
use Modules\Deals\Models\Deal;
use Modules\Identity\Models\User;
use Modules\Patients\Models\Patient;
use Modules\Scheduling\Models\Appointment;
use Modules\Audit\Support\AuditMetadataFormatter;

final class AuditLogIndex extends Component
{
    use WithPagination;

    public string $search = '';
    public string $actionFilter = '';
    public string $entityFilter = '';
    public string $userFilter = '';
    public string $dateFrom = '';
    public string $dateTo = '';

    public ?int $selectedLogId = null;

    public function mount(): void
    {
        $this->authorize('viewAny', AuditLog::class);
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingActionFilter(): void
    {
        $this->resetPage();
    }

    public function updatingEntityFilter(): void
    {
        $this->resetPage();
    }

    public function updatingUserFilter(): void
    {
        $this->resetPage();
    }

    public function updatingDateFrom(): void
    {
        $this->resetPage();
    }

    public function updatingDateTo(): void
    {
        $this->resetPage();
    }

    public function resetFilters(): void
    {
        $this->reset([
            'search',
            'actionFilter',
            'entityFilter',
            'userFilter',
            'dateFrom',
            'dateTo',
        ]);
        $this->resetPage();
    }

    public function inspectLog(int $logId): void
    {
        $log = AuditLog::findOrFail($logId);
        $this->authorize('view', $log);
        $this->selectedLogId = $log->id;
    }

    public function closeModal(): void
    {
        $this->selectedLogId = null;
    }

    public function render(): View
    {
        $logs = AuditLog::query()
            ->with('user')
            ->when($this->actionFilter !== '', fn (Builder $q): Builder => $q->where('action', $this->actionFilter))
            ->when($this->entityFilter !== '', fn (Builder $q): Builder => $q->where('auditable_type', $this->entityFilter))
            ->when($this->userFilter !== '', fn (Builder $q): Builder => $q->where('user_id', (int) $this->userFilter))
            ->when($this->dateFrom !== '', fn (Builder $q): Builder => $q->whereDate('created_at', '>=', $this->dateFrom))
            ->when($this->dateTo !== '', fn (Builder $q): Builder => $q->whereDate('created_at', '<=', $this->dateTo))
            ->when(trim($this->search) !== '', function (Builder $q): void {
                $term = '%' . trim($this->search) . '%';
                $q->where(function (Builder $sub) use ($term): void {
                    $sub->whereRaw('CAST(auditable_id AS TEXT) ILIKE ?', [$term])
                        ->orWhereRaw('CAST(ip_address AS TEXT) ILIKE ?', [$term])
                        ->orWhereRaw('CAST(metadata AS TEXT) ILIKE ?', [$term])
                        ->orWhereHas('user', fn (Builder $u): Builder => $u->where('name', 'ILIKE', $term));
                });
            })
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->paginate(20);

        $selectedLog = $this->selectedLogId !== null
            ? AuditLog::with('user')->find($this->selectedLogId)
            : null;

        $selectedLogDetails = $selectedLog !== null
            ? app(AuditMetadataFormatter::class)->format($selectedLog->metadata)
            : [];

        return view('audit::livewire.audit-log-index', [
            'logs' => $logs,
            'selectedLog' => $selectedLog,
            'selectedLogDetails' => $selectedLogDetails,
            'actions' => AuditAction::cases(),
            'entities' => [
                Patient::class => 'Пациенты',
                Deal::class => 'Сделки',
                Appointment::class => 'Визиты',
                Payment::class => 'Платежи',
            ],
            'users' => User::query()->orderBy('name')->get(['id', 'name']),
        ])->layout('layouts.app', ['title' => 'Журнал аудита (152-ФЗ)']);
    }
}
