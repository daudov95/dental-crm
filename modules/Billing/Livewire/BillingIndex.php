<?php

declare(strict_types=1);

namespace Modules\Billing\Livewire;

use Carbon\CarbonImmutable;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use InvalidArgumentException;
use Livewire\Component;
use Modules\Billing\Actions\RecordPaymentAction;
use Modules\Billing\DTO\PaymentDTO;
use Modules\Billing\Enums\PaymentMethod;
use Modules\Billing\Jobs\CheckOverdueInstallmentsJob;
use Modules\Billing\Jobs\GenerateTomorrowRemindersJob;
use Modules\Billing\Models\Payment;
use Modules\Billing\Models\SystemAlert;
use Modules\Deals\Enums\DealStatus;
use Modules\Deals\Models\Deal;

final class BillingIndex extends Component
{
    public string $activeTab = 'payments'; // payments | installments | alerts
    public string $search = '';
    public string $methodFilter = '';

    public bool $showModal = false;
    public string $dealId = '';
    public string $amount = '';
    public string $method = 'card';
    public string $paidAt = '';
    public string $nextPaymentDate = '';
    public string $comment = '';

    public function mount(): void
    {
        $this->authorize('viewAny', Payment::class);
    }

    public function setTab(string $tab): void
    {
        if (in_array($tab, ['payments', 'installments', 'alerts'], true)) {
            $this->activeTab = $tab;
        }
    }

    public function openPaymentModal(?int $presetDealId = null): void
    {
        $this->authorize('create', Payment::class);

        $this->resetValidation();
        $this->dealId = $presetDealId ? (string) $presetDealId : '';
        $this->method = PaymentMethod::Card->value;
        $this->paidAt = now()->format('Y-m-d\TH:i');
        $this->nextPaymentDate = '';
        $this->comment = '';
        $this->amount = '';

        if ($presetDealId !== null) {
            $this->prefillDealDefaults($presetDealId);
        }

        $this->showModal = true;
    }

    public function updatedDealId(string $value): void
    {
        if ($value !== '') {
            $this->prefillDealDefaults((int) $value);
        } else {
            $this->amount = '';
            $this->nextPaymentDate = '';
        }
    }

    private function prefillDealDefaults(int $dealId): void
    {
        $deal = Deal::find($dealId);
        if (! $deal) {
            return;
        }

        $paid = (float) Payment::where('deal_id', $deal->id)->sum('amount');
        $remaining = max(0.0, round((float) $deal->total_amount - $paid, 2));

        $this->amount = $remaining > 0 ? (string) $remaining : '';
        $this->nextPaymentDate = $deal->is_installment
            ? now()->addMonth()->toDateString()
            : '';
    }

    public function recordPayment(RecordPaymentAction $action): void
    {
        $this->authorize('create', Payment::class);

        $methodValues = array_map(fn (PaymentMethod $m): string => $m->value, PaymentMethod::cases());

        $this->validate([
            'dealId' => ['required', 'integer', 'exists:deals,id'],
            'amount' => ['required', 'numeric', 'min:1'],
            'method' => ['required', Rule::in($methodValues)],
            'paidAt' => ['required', 'date'],
            'nextPaymentDate' => ['nullable', 'date'],
            'comment' => ['nullable', 'string', 'max:1000'],
        ], [
            'dealId.required' => 'Выберите сделку.',
            'amount.required' => 'Укажите сумму платежа.',
            'amount.min' => 'Сумма должна быть больше 0.',
        ]);

        $dto = new PaymentDTO(
            dealId: (int) $this->dealId,
            receivedByUserId: (int) auth()->id(),
            amount: (float) $this->amount,
            method: PaymentMethod::from($this->method),
            paidAt: CarbonImmutable::parse($this->paidAt),
            comment: trim($this->comment) !== '' ? trim($this->comment) : null,
            nextPaymentDate: $this->nextPaymentDate !== '' ? $this->nextPaymentDate : null,
        );

        try {
            $action->execute($dto, auth()->id(), request()->ip());
        } catch (InvalidArgumentException $e) {
            $this->addError('amount', $e->getMessage());

            return;
        }

        $this->showModal = false;
        session()->flash('status', 'Платеж зафиксирован, остаток по сделке пересчитан.');
    }

    public function markAlertRead(int $alertId): void
    {
        $this->authorize('manageAlerts', Payment::class);

        SystemAlert::where('id', $alertId)->update(['is_read' => true]);
    }

    public function markAllAlertsRead(): void
    {
        $this->authorize('manageAlerts', Payment::class);

        SystemAlert::unread()->update(['is_read' => true]);
        session()->flash('status', 'Все уведомления отмечены как прочитанные.');
    }

    public function runBackgroundChecks(): void
    {
        $this->authorize('manageAlerts', Payment::class);

        CheckOverdueInstallmentsJob::dispatch();
        GenerateTomorrowRemindersJob::dispatch();

        session()->flash('status', 'Фоновые проверки отправлены в очередь Redis (просрочки рассрочек и напоминания на завтра).');
    }

    public function render(): View
    {
        $payments = Payment::query()
            ->with(['deal.patient', 'receiver'])
            ->when($this->methodFilter !== '', fn ($q) => $q->where('method', $this->methodFilter))
            ->when($this->search !== '', function ($query): void {
                $term = trim($this->search);
                $query->where(function ($q) use ($term): void {
                    $q->where('comment', 'ilike', "%{$term}%")
                        ->orWhereHas('deal', function ($dq) use ($term): void {
                            $dq->where('title', 'ilike', "%{$term}%")
                                ->orWhereHas('patient', function ($pq) use ($term): void {
                                    $pq->withTrashed()
                                        ->where('full_name', 'ilike', "%{$term}%")
                                        ->orWhere('phone', 'ilike', "%{$term}%");
                                });
                        });
                });
            })
            ->orderByDesc('paid_at')
            ->get();

        $paidByDeal = Payment::query()
            ->select('deal_id', DB::raw('SUM(amount) as paid_total'))
            ->groupBy('deal_id')
            ->pluck('paid_total', 'deal_id');

        $closedStatuses = array_map(fn (DealStatus $s): string => $s->value, DealStatus::closedCases());

        $installmentDeals = Deal::query()
            ->with(['patient', 'doctor'])
            ->where('is_installment', true)
            ->whereNotIn('status', $closedStatuses)
            ->orderByDesc('is_overdue')
            ->orderBy('next_payment_date')
            ->get()
            ->map(function (Deal $deal) use ($paidByDeal): array {
                $paid = round((float) ($paidByDeal[$deal->id] ?? 0), 2);
                $total = round((float) $deal->total_amount, 2);
                $remaining = max(0.0, round($total - $paid, 2));

                return [
                    'deal' => $deal,
                    'total' => $total,
                    'paid' => $paid,
                    'remaining' => $remaining,
                ];
            });

        $alerts = SystemAlert::query()
            ->orderBy('is_read')
            ->orderByDesc('created_at')
            ->limit(50)
            ->get();

        $unreadAlertsCount = SystemAlert::unread()->count();
        $totalRevenue = (float) Payment::sum('amount');
        $monthRevenue = (float) Payment::where('paid_at', '>=', now()->startOfMonth())->sum('amount');
        $totalReceivables = $installmentDeals->sum('remaining');
        $overdueCount = $installmentDeals->filter(fn (array $row): bool => (bool) $row['deal']->is_overdue)->count();

        $selectedDealSummary = null;
        if ($this->dealId !== '') {
            $selectedDeal = Deal::with('patient')->find((int) $this->dealId);
            if ($selectedDeal) {
                $paid = round((float) ($paidByDeal[$selectedDeal->id] ?? 0), 2);
                $total = round((float) $selectedDeal->total_amount, 2);
                $selectedDealSummary = [
                    'deal' => $selectedDeal,
                    'total' => $total,
                    'paid' => $paid,
                    'remaining' => max(0.0, round($total - $paid, 2)),
                ];
            }
        }

        $availableDeals = Deal::query()
            ->with('patient')
            ->where('status', '!=', DealStatus::Lost->value)
            ->orderByDesc('updated_at')
            ->get();

        return view('billing::livewire.billing-index', [
            'payments' => $payments,
            'installmentDeals' => $installmentDeals,
            'alerts' => $alerts,
            'unreadAlertsCount' => $unreadAlertsCount,
            'totalRevenue' => $totalRevenue,
            'monthRevenue' => $monthRevenue,
            'totalReceivables' => $totalReceivables,
            'overdueCount' => $overdueCount,
            'methods' => PaymentMethod::cases(),
            'availableDeals' => $availableDeals,
            'selectedDealSummary' => $selectedDealSummary,
        ])->layout('layouts.app', ['title' => 'Оплаты и рассрочки']);
    }
}
