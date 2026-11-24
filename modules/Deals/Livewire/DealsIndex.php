<?php

declare(strict_types=1);

namespace Modules\Deals\Livewire;

use Illuminate\Contracts\View\View;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Modules\Catalog\Models\Service;
use Modules\Deals\Actions\CreateDealAction;
use Modules\Deals\Actions\MoveDealStageAction;
use Modules\Deals\Actions\UpdateDealAction;
use Modules\Deals\Actions\ReorderDealsAction;
use Modules\Deals\DTO\DealDTO;
use Modules\Deals\DTO\DealItemDTO;
use Modules\Deals\Enums\DealStatus;
use Modules\Deals\Enums\LossReason;
use Modules\Deals\Models\Deal;
use Modules\Identity\Enums\UserRole;
use Modules\Identity\Models\User;
use Modules\Patients\Models\Patient;

class DealsIndex extends Component
{
    public string $viewMode = 'kanban';
    public string $search = '';
    public string $doctorFilter = '';
    public string $statusFilter = '';
    public string $installmentFilter = '';

    // Модалка создания / редактирования сделки
    public bool $showModal = false;
    public ?int $editingId = null;
    public string $patientId = '';
    public string $doctorId = '';
    public string $title = '';
    public string $status = 'new';
    public string $lossReason = '';
    public bool $isInstallment = false;
    public string $nextPaymentDate = '';

    /** @var array<int, array{service_id: string, quantity: int, unit_price: string}> */
    public array $items = [];

    // Модалка выбора причины отказа при смене этапа
    public bool $showLossModal = false;
    public ?int $pendingLossDealId = null;
    public string $pendingLossReason = '';
    /** @var list<int> */
    public array $pendingLossOrderedIds = [];
    public string $stageGroupFilter = 'all';

    public function mount(): void
    {
        $this->authorize('viewAny', Deal::class);
    }

    public function setViewMode(string $mode): void
    {
        if (in_array($mode, ['kanban', 'table'], true)) {
            $this->viewMode = $mode;
        }
    }

    public function openCreateModal(): void
    {
        $this->authorize('create', Deal::class);

        $this->resetForm();
        $this->addItemRow();
        $this->showModal = true;
    }

    public function openEditModal(int $dealId): void
    {
        $deal = Deal::with('items')->findOrFail($dealId);
        $this->authorize('update', $deal);

        $this->resetValidation();
        $this->editingId = $deal->id;
        $this->patientId = (string) $deal->patient_id;
        $this->doctorId = $deal->doctor_id ? (string) $deal->doctor_id : '';
        $this->title = $deal->title;
        $this->status = $deal->status->value;
        $this->lossReason = $deal->loss_reason?->value ?? '';
        $this->isInstallment = (bool) $deal->is_installment;
        $this->nextPaymentDate = $deal->next_payment_date?->format('Y-m-d') ?? '';

        $this->items = $deal->items->map(fn ($item): array => [
            'service_id' => (string) $item->service_id,
            'quantity' => (int) $item->quantity,
            'unit_price' => (string) (float) $item->unit_price,
        ])->values()->all();

        if ($this->items === []) {
            $this->addItemRow();
        }

        $this->showModal = true;
    }

    public function addItemRow(): void
    {
        $this->items[] = [
            'service_id' => '',
            'quantity' => 1,
            'unit_price' => '0',
        ];
    }

    public function removeItemRow(int $index): void
    {
        unset($this->items[$index]);
        $this->items = array_values($this->items);
    }

    public function updatedItems(mixed $value, ?string $key = null): void
    {
        if ($key !== null && str_ends_with($key, '.service_id')) {
            $index = (int) explode('.', $key)[0];
            $service = Service::find($value);

            if ($service && isset($this->items[$index])) {
                $this->items[$index]['unit_price'] = (string) (float) $service->price;
            }
        }
    }

    public function updatedStageGroupFilter(): void
    {
        $this->statusFilter = '';
    }

    public function getCalculatedTotalProperty(): float
    {
        $sum = 0.0;
        foreach ($this->items as $item) {
            $qty = max(0, (int) ($item['quantity'] ?? 0));
            $price = max(0.0, (float) ($item['unit_price'] ?? 0));
            $sum += $qty * $price;
        }

        return round($sum, 2);
    }

    public function save(CreateDealAction $createAction, UpdateDealAction $updateAction): void
    {
        $deal = $this->editingId ? Deal::findOrFail($this->editingId) : null;

        if ($deal) {
            $this->authorize('update', $deal);
        } else {
            $this->authorize('create', Deal::class);
        }

        $statusValues = array_map(fn (DealStatus $s) => $s->value, DealStatus::cases());
        $lossReasonValues = array_map(fn (LossReason $r) => $r->value, LossReason::cases());

        // Фильтруем полностью пустые строки услуг, если пользователь не выбрал услугу
        $filteredItems = array_values(array_filter(
            $this->items,
            fn (array $row): bool => ($row['service_id'] ?? '') !== ''
        ));
        $this->items = $filteredItems;

        $this->validate([
            'patientId' => ['required', 'integer', 'exists:patients,id'],
            'doctorId' => ['nullable', 'integer', 'exists:users,id'],
            'title' => ['required', 'string', 'max:255'],
            'status' => ['required', Rule::in($statusValues)],
            'lossReason' => $this->status === DealStatus::Lost->value
                ? ['required', Rule::in($lossReasonValues)]
                : ['nullable'],
            'isInstallment' => ['boolean'],
            'nextPaymentDate' => ['nullable', 'date'],
            'items' => ['array'],
            'items.*.service_id' => ['required', 'integer', 'exists:services,id'],
            'items.*.quantity' => ['required', 'integer', 'min:1', 'max:100'],
            'items.*.unit_price' => ['required', 'numeric', 'min:0'],
        ], [
            'patientId.required' => 'Выберите пациента.',
            'title.required' => 'Укажите название сделки.',
            'lossReason.required' => 'Для статуса «Отказ» необходимо выбрать причину.',
            'items.*.service_id.required' => 'Выберите услугу.',
        ]);

        $itemDtos = array_map(
            fn (array $row): DealItemDTO => new DealItemDTO(
                serviceId: (int) $row['service_id'],
                quantity: (int) $row['quantity'],
                unitPrice: (float) $row['unit_price'],
            ),
            $this->items
        );

        $dto = new DealDTO(
            patientId: (int) $this->patientId,
            doctorId: $this->doctorId !== '' ? (int) $this->doctorId : null,
            title: trim($this->title),
            status: DealStatus::from($this->status),
            lossReason: $this->status === DealStatus::Lost->value ? LossReason::from($this->lossReason) : null,
            isInstallment: $this->isInstallment,
            nextPaymentDate: $this->isInstallment && $this->nextPaymentDate !== '' ? $this->nextPaymentDate : null,
            items: $itemDtos,
        );

        $actorId = auth()->id();
        $ip = request()->ip();

        if ($deal) {
            $updateAction->execute($deal, $dto, $actorId, $ip);
            session()->flash('status', 'Сделка обновлена.');
        } else {
            $createAction->execute($dto, $actorId, $ip);
            session()->flash('status', 'Сделка создана.');
        }

        $this->showModal = false;
        $this->resetForm();
    }

    public function moveStage(int $dealId, string $newStatusValue, MoveDealStageAction $action): void
    {
        $deal = Deal::findOrFail($dealId);
        $this->authorize('moveStage', $deal);

        $newStatus = DealStatus::from($newStatusValue);

        if ($newStatus === DealStatus::Lost) {
            $this->pendingLossDealId = $deal->id;
            $this->pendingLossReason = '';
            $this->resetValidation('pendingLossReason');
            $this->showLossModal = true;

            return;
        }

        $action->execute($deal, $newStatus, null, auth()->id(), request()->ip());
        session()->flash('status', "Сделка переведена в статус «{$newStatus->label()}».");
    }

    public function confirmLoss(MoveDealStageAction $action, ReorderDealsAction $reorderAction): void
    {
        if (! $this->pendingLossDealId) {
            return;
        }

        $deal = Deal::findOrFail($this->pendingLossDealId);
        $this->authorize('moveStage', $deal);

        $lossReasonValues = array_map(fn (LossReason $r) => $r->value, LossReason::cases());

        $this->validate([
            'pendingLossReason' => ['required', Rule::in($lossReasonValues)],
        ], [
            'pendingLossReason.required' => 'Укажите причину отказа.',
        ]);

        $action->execute(
            $deal,
            DealStatus::Lost,
            LossReason::from($this->pendingLossReason),
            auth()->id(),
            request()->ip(),
        );

        if ($this->pendingLossOrderedIds !== []) {
            $reorderAction->execute(DealStatus::Lost, $this->pendingLossOrderedIds, auth()->id(), request()->ip());
        }

        $this->showLossModal = false;
        $this->pendingLossDealId = null;
        $this->pendingLossReason = '';
        $this->pendingLossOrderedIds = [];

        session()->flash('status', 'Сделка закрыта со статусом «Отказ».');
    }

    private function resetForm(): void
    {
        $this->resetValidation();
        $this->editingId = null;
        $this->patientId = '';
        $this->doctorId = '';
        $this->title = '';
        $this->status = DealStatus::New->value;
        $this->lossReason = '';
        $this->isInstallment = false;
        $this->nextPaymentDate = '';
        $this->items = [];
    }

    /**
     * @param array<int, int|string> $orderedIds
     */
    public function syncColumnOrder(
        string $statusValue,
        array $orderedIds,
        ?int $movedDealId,
        ReorderDealsAction $action,
    ): void {
        $this->authorize('create', Deal::class);

        $targetStatus = DealStatus::from($statusValue);
        $ids = array_values(array_map('intval', $orderedIds));

        if ($targetStatus === DealStatus::Lost && $movedDealId !== null) {
            $movedDeal = Deal::find($movedDealId);
            if ($movedDeal && $movedDeal->status !== DealStatus::Lost) {
                $this->pendingLossDealId = $movedDeal->id;
                $this->pendingLossReason = '';
                $this->pendingLossOrderedIds = $ids;
                $this->resetValidation('pendingLossReason');
                $this->showLossModal = true;

                return;
            }
        }

        $action->execute($targetStatus, $ids, auth()->id(), request()->ip());
    }

    public function render(): View
    {
        /** @var User $user */
        $user = auth()->user();

        $visibleStatuses = match ($this->stageGroupFilter) {
            'active' => DealStatus::activeCases(),
            'closed' => DealStatus::closedCases(),
            default => DealStatus::cases(),
        };

        $visibleStatusValues = array_map(fn (DealStatus $s): string => $s->value, $visibleStatuses);

        $deals = Deal::query()
            ->with(['patient', 'doctor', 'items'])
            ->visibleFor($user)
            ->when($this->stageGroupFilter !== 'all', fn ($q) => $q->whereIn('status', $visibleStatusValues))
            ->when($this->search !== '', function ($query): void {
                $term = trim($this->search);
                $query->where(function ($q) use ($term): void {
                    $q->where('title', 'ilike', "%{$term}%")
                        ->orWhereHas('patient', function ($pq) use ($term): void {
                            $pq->withTrashed()
                                ->where('full_name', 'ilike', "%{$term}%")
                                ->orWhere('phone', 'ilike', "%{$term}%");
                        });
                });
            })
            ->when($this->doctorFilter !== '', fn ($q) => $q->where('doctor_id', (int) $this->doctorFilter))
            ->when($this->statusFilter !== '', fn ($q) => $q->where('status', $this->statusFilter))
            ->when($this->installmentFilter === 'yes', fn ($q) => $q->where('is_installment', true))
            ->when($this->installmentFilter === 'no', fn ($q) => $q->where('is_installment', false))
            ->orderBy('sort_order')
            ->orderByDesc('updated_at')
            ->get();

        $dealsByStatus = [];
        foreach (DealStatus::cases() as $stage) {
            $dealsByStatus[$stage->value] = $deals->filter(fn (Deal $d): bool => $d->status === $stage)->values();
        }

        return view('deals::livewire.deals-index', [
            'deals' => $deals,
            'dealsByStatus' => $dealsByStatus,
            'statuses' => DealStatus::cases(),
            'visibleStatuses' => $visibleStatuses,
            'lossReasons' => LossReason::cases(),
            'patients' => Patient::orderBy('full_name')->get(),
            'doctors' => User::where('role', UserRole::Doctor)->where('is_active', true)->orderBy('name')->get(),
            'services' => Service::active()->orderBy('category')->orderBy('name')->get(),
            'canManage' => $user->can('create', Deal::class),
        ])->layout('layouts.app', ['title' => 'Сделки и воронка']);
    }
}
