<?php

declare(strict_types=1);

namespace Modules\Patients\Livewire;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Modules\Billing\Models\Payment;
use Modules\Deals\Enums\DealStatus;
use Modules\Deals\Models\Deal;
use Modules\Identity\Models\User;
use Modules\Patients\Models\Patient;
use Modules\Scheduling\Models\Appointment;

#[Layout('layouts.app')]
#[Title('Карточка пациента')]
final class PatientShow extends Component
{
    use AuthorizesRequests;

    public Patient $patient;

    public function mount(Patient $patient): void
    {
        $this->patient = $patient;
        $this->authorize('view', $this->patient);
    }

    public function render(): View
    {
        $user = auth()->user();

        $deals = Deal::query()
            ->with(['doctor', 'items'])
            ->visibleFor($user)
            ->where('patient_id', $this->patient->id)
            ->orderByDesc('updated_at')
            ->get();

        $appointments = Appointment::query()
            ->with(['doctor', 'deal'])
            ->visibleFor($user)
            ->where('patient_id', $this->patient->id)
            ->orderByDesc('starts_at')
            ->get();

        $canViewBilling = $user instanceof User && $user->can('viewAny', Payment::class);

        $payments = $canViewBilling
            ? Payment::query()
                ->with(['deal', 'receiver'])
                ->whereHas('deal', fn ($q) => $q->where('patient_id', $this->patient->id))
                ->orderByDesc('paid_at')
                ->get()
            : collect();

        $activeDealsTotal = round(
            (float) $deals->where('status', '!=', DealStatus::Lost)->sum('total_amount'),
            2,
        );
        $paidTotal = round((float) $payments->sum('amount'), 2);
        $remainingTotal = max(0.0, round($activeDealsTotal - $paidTotal, 2));

        return view('patients::livewire.patient-show', [
            'deals' => $deals,
            'appointments' => $appointments,
            'payments' => $payments,
            'canViewBilling' => $canViewBilling,
            'activeDealsTotal' => $activeDealsTotal,
            'paidTotal' => $paidTotal,
            'remainingTotal' => $remainingTotal,
        ]);
    }
}
