<?php

declare(strict_types=1);

namespace Modules\Scheduling\Livewire;

use Carbon\CarbonImmutable;
use Illuminate\Contracts\View\View;
use Illuminate\Validation\Rule;
use InvalidArgumentException;
use Livewire\Component;
use Modules\Catalog\Models\Service;
use Modules\Deals\Models\Deal;
use Modules\Identity\Enums\UserRole;
use Modules\Identity\Models\User;
use Modules\Patients\Models\Patient;
use Modules\Scheduling\Actions\ScheduleAppointmentAction;
use Modules\Scheduling\Actions\UpdateAppointmentAction;
use Modules\Scheduling\Actions\UpdateAppointmentStatusAction;
use Modules\Scheduling\DTO\AppointmentDTO;
use Modules\Scheduling\Enums\AppointmentStatus;
use Modules\Scheduling\Exceptions\AppointmentConflictException;
use Modules\Scheduling\Models\Appointment;

final class ScheduleIndex extends Component
{
    public string $viewMode = 'day'; // day | week | tomorrow
    public string $displayMode = 'calendar'; // calendar | list
    public string $selectedDate = '';
    public string $doctorFilter = '';
    public string $statusFilter = '';

    // Модалка создания / редактирования записи (director, admin)
    public bool $showModal = false;
    public ?int $editingId = null;
    public string $patientId = '';
    public string $doctorId = '';
    public string $dealId = '';
    public string $serviceId = '';
    public string $appointmentDate = '';
    public string $startTime = '10:00';
    public int $durationMinutes = 30;
    public string $status = 'scheduled';
    public string $notes = '';

    // Модалка быстрого обновления статуса и заметки (доступна и врачу)
    public bool $showStatusModal = false;
    public ?int $statusAppointmentId = null;
    public string $quickStatus = 'scheduled';
    public string $quickNotes = '';

    public function mount(): void
    {
        $this->authorize('viewAny', Appointment::class);
        $this->selectedDate = now()->toDateString();
    }

    public function setViewMode(string $mode): void
    {
        if (in_array($mode, ['day', 'week', 'tomorrow'], true)) {
            $this->viewMode = $mode;
        }
    }

    public function setDisplayMode(string $mode): void
    {
        if (in_array($mode, ['calendar', 'list'], true)) {
            $this->displayMode = $mode;
        }
    }

    public function shiftDate(int $days): void
    {
        $current = CarbonImmutable::parse($this->selectedDate ?: now()->toDateString());
        $this->selectedDate = $current->addDays($days)->toDateString();
    }

    public function goToday(): void
    {
        $this->selectedDate = now()->toDateString();
    }

    public function updatedServiceId(string $value): void
    {
        if ($value === '') {
            return;
        }

        $service = Service::find((int) $value);
        if ($service) {
            $this->durationMinutes = $service->duration_minutes;
        }
    }

    public function updatedPatientId(): void
    {
        $this->dealId = '';
    }

    public function openCreateModal(
        ?string $presetDate = null,
        ?string $presetTime = null,
        ?int $presetDoctorId = null,
    ): void {
        $this->authorize('create', Appointment::class);

        $this->resetForm();
        $this->appointmentDate = $presetDate ?: $this->selectedDate;

        if ($presetTime !== null && $presetTime !== '') {
            $this->startTime = $presetTime;
        }

        if ($presetDoctorId !== null) {
            $this->doctorId = (string) $presetDoctorId;
        }

        $this->showModal = true;
    }

    public function openEditModal(int $appointmentId): void
    {
        $appointment = Appointment::findOrFail($appointmentId);
        $this->authorize('update', $appointment);

        $this->resetValidation();
        $this->editingId = $appointment->id;
        $this->patientId = (string) $appointment->patient_id;
        $this->doctorId = (string) $appointment->doctor_id;
        $this->dealId = $appointment->deal_id ? (string) $appointment->deal_id : '';
        $this->serviceId = '';
        $this->appointmentDate = $appointment->starts_at->format('Y-m-d');
        $this->startTime = $appointment->starts_at->format('H:i');
        $this->durationMinutes = max(15, $appointment->durationMinutes());
        $this->status = $appointment->status->value;
        $this->notes = $appointment->notes ?? '';

        $this->showModal = true;
    }

    public function save(
        ScheduleAppointmentAction $scheduleAction,
        UpdateAppointmentAction $updateAction,
    ): void {
        $appointment = $this->editingId ? Appointment::findOrFail($this->editingId) : null;

        if ($appointment) {
            $this->authorize('update', $appointment);
        } else {
            $this->authorize('create', Appointment::class);
        }

        $statusValues = array_map(fn (AppointmentStatus $s): string => $s->value, AppointmentStatus::cases());

        $this->validate([
            'patientId' => ['required', 'integer', 'exists:patients,id'],
            'doctorId' => ['required', 'integer', 'exists:users,id'],
            'dealId' => ['nullable', 'integer', 'exists:deals,id'],
            'appointmentDate' => ['required', 'date'],
            'startTime' => ['required', 'date_format:H:i'],
            'durationMinutes' => ['required', 'integer', 'min:10', 'max:480'],
            'status' => ['required', Rule::in($statusValues)],
            'notes' => ['nullable', 'string', 'max:2000'],
        ], [
            'patientId.required' => 'Выберите пациента.',
            'doctorId.required' => 'Выберите врача.',
            'appointmentDate.required' => 'Укажите дату приема.',
            'startTime.required' => 'Укажите время начала.',
        ]);

        $startsAt = CarbonImmutable::parse("{$this->appointmentDate} {$this->startTime}");
        $endsAt = $startsAt->addMinutes($this->durationMinutes);

        $dto = new AppointmentDTO(
            patientId: (int) $this->patientId,
            doctorId: (int) $this->doctorId,
            dealId: $this->dealId !== '' ? (int) $this->dealId : null,
            startsAt: $startsAt,
            endsAt: $endsAt,
            status: AppointmentStatus::from($this->status),
            notes: trim($this->notes) !== '' ? trim($this->notes) : null,
        );

        try {
            if ($appointment) {
                $updateAction->execute($appointment, $dto, auth()->id(), request()->ip());
                session()->flash('status', 'Запись обновлена.');
            } else {
                $scheduleAction->execute($dto, auth()->id(), request()->ip());
                session()->flash('status', 'Пациент записан на прием.');
            }
        } catch (AppointmentConflictException|InvalidArgumentException $e) {
            $this->addError('startTime', $e->getMessage());

            return;
        }

        $this->showModal = false;
        $this->resetForm();
    }

    public function openStatusModal(int $appointmentId): void
    {
        $appointment = Appointment::findOrFail($appointmentId);
        $this->authorize('updateStatus', $appointment);

        $this->resetValidation();
        $this->statusAppointmentId = $appointment->id;
        $this->quickStatus = $appointment->status->value;
        $this->quickNotes = $appointment->notes ?? '';
        $this->showStatusModal = true;
    }

    public function switchToEditFromStatus(): void
    {
        if (! $this->statusAppointmentId) {
            return;
        }

        $id = $this->statusAppointmentId;
        $this->showStatusModal = false;
        $this->openEditModal($id);
    }

    public function saveQuickStatus(UpdateAppointmentStatusAction $action): void
    {
        if (! $this->statusAppointmentId) {
            return;
        }

        $appointment = Appointment::findOrFail($this->statusAppointmentId);
        $this->authorize('updateStatus', $appointment);

        $statusValues = array_map(fn (AppointmentStatus $s): string => $s->value, AppointmentStatus::cases());

        $this->validate([
            'quickStatus' => ['required', Rule::in($statusValues)],
            'quickNotes' => ['nullable', 'string', 'max:2000'],
        ]);

        $action->execute(
            $appointment,
            AppointmentStatus::from($this->quickStatus),
            trim($this->quickNotes) !== '' ? trim($this->quickNotes) : '',
            auth()->id(),
            request()->ip(),
        );

        $this->showStatusModal = false;
        $this->statusAppointmentId = null;

        session()->flash('status', 'Статус визита обновлен.');
    }

    private function resetForm(): void
    {
        $this->resetValidation();
        $this->editingId = null;
        $this->patientId = '';
        $this->doctorId = '';
        $this->dealId = '';
        $this->serviceId = '';
        $this->appointmentDate = $this->selectedDate ?: now()->toDateString();
        $this->startTime = '10:00';
        $this->durationMinutes = 30;
        $this->status = AppointmentStatus::Scheduled->value;
        $this->notes = '';
    }

    public function render(): View
    {
        /** @var User $user */
        $user = auth()->user();

        $anchor = CarbonImmutable::parse($this->selectedDate ?: now()->toDateString());

        if ($this->viewMode === 'tomorrow') {
            $rangeStart = CarbonImmutable::tomorrow()->startOfDay();
            $rangeEnd = CarbonImmutable::tomorrow()->endOfDay();
        } elseif ($this->viewMode === 'week') {
            $rangeStart = $anchor->startOfWeek();
            $rangeEnd = $anchor->endOfWeek();
        } else {
            $rangeStart = $anchor->startOfDay();
            $rangeEnd = $anchor->endOfDay();
        }

        $appointments = Appointment::query()
            ->with(['patient', 'doctor', 'deal'])
            ->visibleFor($user)
            ->whereBetween('starts_at', [$rangeStart, $rangeEnd])
            ->when($this->viewMode === 'tomorrow', fn ($q) => $q->where('status', AppointmentStatus::Scheduled->value))
            ->when($this->doctorFilter !== '', fn ($q) => $q->where('doctor_id', (int) $this->doctorFilter))
            ->when($this->statusFilter !== '' && $this->viewMode !== 'tomorrow', fn ($q) => $q->where('status', $this->statusFilter))
            ->orderBy('starts_at')
            ->get();

        $tomorrowCount = Appointment::query()
            ->visibleFor($user)
            ->whereBetween('starts_at', [CarbonImmutable::tomorrow()->startOfDay(), CarbonImmutable::tomorrow()->endOfDay()])
            ->where('status', AppointmentStatus::Scheduled->value)
            ->count();

        $doctors = User::where('role', UserRole::Doctor)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        // Колонки врачей для дневной календарной сетки
        if ($user->role === UserRole::Doctor) {
            $calendarDoctors = collect([$user]);
        } elseif ($this->doctorFilter !== '') {
            $calendarDoctors = $doctors->where('id', (int) $this->doctorFilter)->values();
        } else {
            $calendarDoctors = $doctors;
        }

        // Базовый рабочий день: 09:00 .. 21:00, с авторасширением под ранние/поздние записи
        $dayStartMinutes = 9 * 60;
        $dayEndMinutes = 21 * 60;

        if ($this->viewMode === 'day' && $appointments->isNotEmpty()) {
            foreach ($appointments as $appt) {
                $startMin = $appt->starts_at->hour * 60 + $appt->starts_at->minute;
                $endMin = $appt->ends_at->toDateString() > $appt->starts_at->toDateString()
                    ? 24 * 60
                    : ($appt->ends_at->hour * 60 + $appt->ends_at->minute);

                if ($startMin < $dayStartMinutes) {
                    $dayStartMinutes = max(0, (int) floor($startMin / 30) * 30);
                }

                if ($endMin > $dayEndMinutes) {
                    $dayEndMinutes = min(24 * 60, (int) ceil($endMin / 30) * 30);
                }
            }
        }

        $timeSlots = [];
        for ($minutes = $dayStartMinutes; $minutes < $dayEndMinutes; $minutes += 30) {
            $timeSlots[] = sprintf('%02d:%02d', intdiv($minutes, 60), $minutes % 60);
        }

        $activeStatusAppointment = $this->statusAppointmentId
            ? Appointment::with(['patient', 'doctor', 'deal'])->find($this->statusAppointmentId)
            : null;

        // 7 дней недели для недельной сетки
        $weekDays = [];
        $weekStart = $anchor->startOfWeek();
        $dayNames = ['Пн', 'Вт', 'Ср', 'Чт', 'Пт', 'Сб', 'Вс'];
        for ($i = 0; $i < 7; $i++) {
            $day = $weekStart->addDays($i);
            $weekDays[] = [
                'date' => $day->toDateString(),
                'label' => $dayNames[$i] . ', ' . $day->format('d.m'),
                'isToday' => $day->isToday(),
                'appointments' => $appointments->filter(
                    fn (Appointment $a): bool => $a->starts_at->toDateString() === $day->toDateString()
                )->values(),
            ];
        }

        $patientDeals = $this->patientId !== ''
            ? Deal::where('patient_id', (int) $this->patientId)->orderByDesc('updated_at')->get()
            : collect();

        return view('scheduling::livewire.schedule-index', [
            'appointments' => $appointments,
            'rangeStart' => $rangeStart,
            'rangeEnd' => $rangeEnd,
            'tomorrowCount' => $tomorrowCount,
            'statuses' => AppointmentStatus::cases(),
            'doctors' => $doctors,
            'calendarDoctors' => $calendarDoctors,
            'timeSlots' => $timeSlots,
            'weekDays' => $weekDays,
            'patients' => Patient::orderBy('full_name')->get(),
            'services' => Service::active()->orderBy('category')->orderBy('name')->get(),
            'patientDeals' => $patientDeals,
            'canManage' => $user->can('create', Appointment::class),
            'isDoctor' => $user->role === UserRole::Doctor,
            'dayStartMinutes' => $dayStartMinutes,
            'dayEndMinutes' => $dayEndMinutes,
            'activeStatusAppointment' => $activeStatusAppointment,
        ])->layout('layouts.app', ['title' => 'Расписание врачей']);
    }
}
