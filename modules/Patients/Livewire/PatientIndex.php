<?php

declare(strict_types=1);

namespace Modules\Patients\Livewire;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;
use Modules\Patients\Actions\ArchivePatientAction;
use Modules\Patients\Actions\CreatePatientAction;
use Modules\Patients\Actions\RestorePatientAction;
use Modules\Patients\Actions\UpdatePatientAction;
use Modules\Patients\DTO\PatientDTO;
use Modules\Patients\Enums\PatientSource;
use Modules\Patients\Models\Patient;

#[Layout('layouts.app')]
#[Title('Пациенты')]
class PatientIndex extends Component
{
    use AuthorizesRequests;
    use WithPagination;

    public string $search = '';
    public string $sourceFilter = '';
    public string $statusFilter = 'active';

    public bool $showModal = false;
    public ?int $editingId = null;

    public string $fullName = '';
    public string $phone = '';
    public string $birthDate = '';
    public string $source = 'instagram';
    public string $notes = '';
    public bool $consentGiven = false;

    public function mount(): void
    {
        $this->authorize('viewAny', Patient::class);
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedSourceFilter(): void
    {
        $this->resetPage();
    }

    public function updatedStatusFilter(): void
    {
        $this->resetPage();
    }

    public function openCreateModal(): void
    {
        $this->authorize('create', Patient::class);
        $this->resetForm();
        $this->showModal = true;
    }

    public function openEditModal(int $patientId): void
    {
        $patient = Patient::withTrashed()->findOrFail($patientId);
        $this->authorize('update', $patient);

        $this->editingId = $patient->id;
        $this->fullName = $patient->full_name;
        $this->phone = $patient->phone;
        $this->birthDate = $patient->birth_date?->format('Y-m-d') ?? '';
        $this->source = $patient->source->value;
        $this->notes = $patient->notes ?? '';
        $this->consentGiven = $patient->consent_given;
        $this->resetValidation();
        $this->showModal = true;
    }

    public function save(
        CreatePatientAction $createAction,
        UpdatePatientAction $updateAction,
    ): void {
        $this->validate([
            'fullName' => ['required', 'string', 'max:255'],
            'phone' => [
                'required',
                'string',
                'max:32',
                Rule::unique('patients', 'phone')->ignore($this->editingId),
            ],
            'birthDate' => ['nullable', 'date', 'before_or_equal:today'],
            'source' => ['required', Rule::enum(PatientSource::class)],
            'notes' => ['nullable', 'string', 'max:2000'],
            'consentGiven' => ['boolean'],
        ]);

        $dto = new PatientDTO(
            fullName: $this->fullName,
            phone: $this->phone,
            birthDate: $this->birthDate !== '' ? $this->birthDate : null,
            source: PatientSource::from($this->source),
            notes: $this->notes !== '' ? $this->notes : null,
            consentGiven: $this->consentGiven,
        );

        $actorId = auth()->id();
        $ip = request()->ip();

        if ($this->editingId !== null) {
            $patient = Patient::withTrashed()->findOrFail($this->editingId);
            $this->authorize('update', $patient);
            $updateAction->execute($patient, $dto, $actorId, $ip);
            session()->flash('status', 'Карточка пациента обновлена.');
        } else {
            $this->authorize('create', Patient::class);
            $createAction->execute($dto, $actorId, $ip);
            session()->flash('status', 'Пациент добавлен в базу.');
        }

        $this->showModal = false;
        $this->resetForm();
    }

    public function archive(int $patientId, ArchivePatientAction $action): void
    {
        $patient = Patient::findOrFail($patientId);
        $this->authorize('delete', $patient);

        $action->execute($patient, auth()->id(), request()->ip());
        session()->flash('status', 'Пациент перенесен в архив.');
    }

    public function restore(int $patientId, RestorePatientAction $action): void
    {
        $patient = Patient::withTrashed()->findOrFail($patientId);
        $this->authorize('restore', $patient);

        $action->execute($patientId);
        session()->flash('status', 'Пациент восстановлен из архива.');
    }

    private function resetForm(): void
    {
        $this->editingId = null;
        $this->fullName = '';
        $this->phone = '';
        $this->birthDate = '';
        $this->source = PatientSource::Instagram->value;
        $this->notes = '';
        $this->consentGiven = false;
        $this->resetValidation();
    }

    public function render(): View
    {
        $query = Patient::query();

        if ($this->statusFilter === 'archived') {
            $query->onlyTrashed();
        } elseif ($this->statusFilter === 'all') {
            $query->withTrashed();
        }

        $patients = $query
            ->when($this->search !== '', function ($q): void {
                $term = '%' . mb_strtolower($this->search) . '%';
                $q->where(function ($sub) use ($term): void {
                    $sub->whereRaw('LOWER(full_name) LIKE ?', [$term])
                        ->orWhereRaw('LOWER(phone) LIKE ?', [$term]);
                });
            })
            ->when($this->sourceFilter !== '', fn ($q) => $q->where('source', $this->sourceFilter))
            ->orderByDesc('created_at')
            ->paginate(12);

        return view('patients::livewire.patient-index', [
            'patients' => $patients,
            'sources' => PatientSource::cases(),
            'canManage' => auth()->user()->can('create', Patient::class),
        ]);
    }
}
