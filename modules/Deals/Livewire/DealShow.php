<?php

declare(strict_types=1);

namespace Modules\Deals\Livewire;

use Illuminate\Contracts\View\View;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Modules\Billing\Models\Payment;
use Modules\Deals\Actions\MoveDealStageAction;
use Modules\Deals\Enums\DealStatus;
use Modules\Deals\Enums\LossReason;
use Modules\Deals\Models\Deal;
use Modules\Scheduling\Models\Appointment;

class DealShow extends Component
{
    public Deal $deal;
    public bool $showLossModal = false;
    public string $pendingLossReason = '';

    public function mount(Deal $deal): void
    {
        $this->authorize('view', $deal);
        $this->deal = $deal->load(['patient', 'doctor', 'items.service']);
    }

    public function moveStage(string $newStatusValue, MoveDealStageAction $action): void
    {
        $this->authorize('moveStage', $this->deal);

        $newStatus = DealStatus::from($newStatusValue);

        if ($newStatus === DealStatus::Lost) {
            $this->pendingLossReason = '';
            $this->resetValidation('pendingLossReason');
            $this->showLossModal = true;

            return;
        }

        $this->deal = $action->execute($this->deal, $newStatus, null, auth()->id(), request()->ip())
            ->load(['patient', 'doctor', 'items.service']);

        session()->flash('status', "Этап сделки обновлен: «{$newStatus->label()}».");
    }

    public function confirmLoss(MoveDealStageAction $action): void
    {
        $this->authorize('moveStage', $this->deal);

        $lossReasonValues = array_map(fn (LossReason $r) => $r->value, LossReason::cases());

        $this->validate([
            'pendingLossReason' => ['required', Rule::in($lossReasonValues)],
        ], [
            'pendingLossReason.required' => 'Укажите причину отказа.',
        ]);

        $this->deal = $action->execute(
            $this->deal,
            DealStatus::Lost,
            LossReason::from($this->pendingLossReason),
            auth()->id(),
            request()->ip(),
        )->load(['patient', 'doctor', 'items.service']);

        $this->showLossModal = false;
        $this->pendingLossReason = '';

        session()->flash('status', 'Сделка переведена в статус «Отказ».');
    }

    public function render(): View
    {
        $appointments = Appointment::query()
            ->with('doctor')
            ->where('deal_id', $this->deal->id)
            ->orderByDesc('starts_at')
            ->get();

        $payments = Payment::query()
            ->with('receiver')
            ->where('deal_id', $this->deal->id)
            ->orderByDesc('paid_at')
            ->get();

        $paidTotal = round((float) $payments->sum('amount'), 2);
        $remainingAmount = max(0.0, round((float) $this->deal->total_amount - $paidTotal, 2));

        return view('deals::livewire.deal-show', [
            'statuses' => DealStatus::cases(),
            'lossReasons' => LossReason::cases(),
            'appointments' => $appointments,
            'payments' => $payments,
            'paidTotal' => $paidTotal,
            'remainingAmount' => $remainingAmount,
            'canManage' => auth()->user()->can('moveStage', $this->deal),
            'canViewBilling' => auth()->user()->can('viewAny', Payment::class),
        ])->layout('layouts.app', ['title' => $this->deal->title]);
    }
}
