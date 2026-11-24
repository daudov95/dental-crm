<?php

declare(strict_types=1);

namespace Modules\Patients\Livewire;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Modules\Patients\Models\Patient;

#[Layout('layouts.app')]
#[Title('Карточка пациента')]
class PatientShow extends Component
{
    use AuthorizesRequests;

    public Patient $patient;

    public function mount(Patient $patient): void
    {
        $this->authorize('view', $patient);
        $this->patient = $patient;
    }

    public function render(): View
    {
        return view('patients::livewire.patient-show');
    }
}
