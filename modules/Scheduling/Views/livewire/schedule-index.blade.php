<div class="space-y-6">
    @if (session('status'))
        <div class="rounded-lg bg-emerald-50 border border-emerald-200 px-4 py-3 text-sm text-emerald-800">
            {{ session('status') }}
        </div>
    @endif

    {{-- Верхняя панель: режимы, навигация по датам, фильтры и кнопка записи --}}
    <div class="flex flex-wrap items-center justify-between gap-4">
        <div class="flex flex-wrap items-center gap-3">
            <div class="inline-flex rounded-lg border border-slate-300 bg-white p-0.5 text-sm">
                <button type="button" wire:click="setViewMode('day')"
                        class="px-3 py-1.5 rounded-md font-medium transition {{ $viewMode === 'day' ? 'bg-sky-600 text-white' : 'text-slate-600 hover:text-slate-900' }}">
                    День
                </button>
                <button type="button" wire:click="setViewMode('week')"
                        class="px-3 py-1.5 rounded-md font-medium transition {{ $viewMode === 'week' ? 'bg-sky-600 text-white' : 'text-slate-600 hover:text-slate-900' }}">
                    Неделя
                </button>
                <button type="button" wire:click="setViewMode('tomorrow')"
                        class="px-3 py-1.5 rounded-md font-medium transition flex items-center gap-1.5 {{ $viewMode === 'tomorrow' ? 'bg-amber-500 text-white' : 'text-slate-600 hover:text-slate-900' }}">
                    Напоминания на завтра
                    @if($tomorrowCount > 0)
                        <span class="px-1.5 py-0.5 rounded-full text-[11px] {{ $viewMode === 'tomorrow' ? 'bg-white/25 text-white' : 'bg-amber-100 text-amber-800' }}">
                            {{ $tomorrowCount }}
                        </span>
                    @endif
                </button>
            </div>

            @if($viewMode !== 'tomorrow')
                <div class="inline-flex items-center gap-1">
                    <button type="button" wire:click="shiftDate({{ $viewMode === 'week' ? -7 : -1 }})"
                            class="px-2.5 py-1.5 rounded-lg border border-slate-300 bg-white text-sm text-slate-700 hover:bg-slate-50">
                        ←
                    </button>
                    <input type="date" wire:model.live="selectedDate"
                           class="rounded-lg border border-slate-300 bg-white px-3 py-1.5 text-sm">
                    <button type="button" wire:click="shiftDate({{ $viewMode === 'week' ? 7 : 1 }})"
                            class="px-2.5 py-1.5 rounded-lg border border-slate-300 bg-white text-sm text-slate-700 hover:bg-slate-50">
                        →
                    </button>
                    <button type="button" wire:click="goToday"
                            class="ml-1 px-3 py-1.5 rounded-lg border border-slate-300 bg-white text-sm text-slate-700 hover:bg-slate-50">
                        Сегодня
                    </button>
                </div>
            @endif
        </div>

        <div class="flex flex-wrap items-center gap-3">
            @if(! $isDoctor)
                <select wire:model.live="doctorFilter"
                        class="rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm">
                    <option value="">Все врачи</option>
                    @foreach($doctors as $doc)
                        <option value="{{ $doc->id }}">{{ $doc->name }}</option>
                    @endforeach
                </select>
            @endif

            @if($viewMode !== 'tomorrow')
                <select wire:model.live="statusFilter"
                        class="rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm">
                    <option value="">Все статусы</option>
                    @foreach($statuses as $st)
                        <option value="{{ $st->value }}">{{ $st->label() }}</option>
                    @endforeach
                </select>
            @endif

            @if($viewMode !== 'tomorrow')
                <div class="inline-flex rounded-lg border border-slate-300 bg-white p-0.5 text-sm">
                    <button type="button" wire:click="setDisplayMode('calendar')"
                            class="px-3 py-1.5 rounded-md font-medium transition {{ $displayMode === 'calendar' ? 'bg-slate-800 text-white' : 'text-slate-600 hover:text-slate-900' }}">
                        Календарь
                    </button>
                    <button type="button" wire:click="setDisplayMode('list')"
                            class="px-3 py-1.5 rounded-md font-medium transition {{ $displayMode === 'list' ? 'bg-slate-800 text-white' : 'text-slate-600 hover:text-slate-900' }}">
                        Список
                    </button>
                </div>
            @endif

            @if($canManage)
                <button wire:click="openCreateModal"
                        class="bg-sky-600 hover:bg-sky-700 text-white text-sm font-medium px-4 py-2 rounded-lg transition">
                    + Записать на прием
                </button>
            @endif
        </div>
    </div>

    {{-- Заголовок текущего периода --}}
    <div class="flex items-center justify-between text-sm text-slate-600">
        <div>
            @if($viewMode === 'tomorrow')
                Запланированные визиты на завтра (<strong>{{ $rangeStart->format('d.m.Y') }}</strong>) — список для обзвона и подтверждения
            @elseif($viewMode === 'week')
                Период: <strong>{{ $rangeStart->format('d.m.Y') }} — {{ $rangeEnd->format('d.m.Y') }}</strong>
            @else
                Расписание на <strong>{{ $rangeStart->format('d.m.Y') }}</strong>
            @endif
        </div>
        <div>Всего записей: <strong>{{ $appointments->count() }}</strong></div>
    </div>

    @php
        $badgeStyles = [
            'scheduled' => 'bg-sky-50 text-sky-700',
            'completed' => 'bg-emerald-50 text-emerald-700',
            'no_show' => 'bg-amber-50 text-amber-700',
            'cancelled' => 'bg-rose-50 text-rose-700',
        ];
        $cardStyles = [
            'scheduled' => 'bg-sky-50 border-sky-300 text-sky-950 hover:bg-sky-100/80',
            'completed' => 'bg-emerald-50 border-emerald-300 text-emerald-950 hover:bg-emerald-100/80',
            'no_show' => 'bg-amber-50 border-amber-300 text-amber-950 hover:bg-amber-100/80',
            'cancelled' => 'bg-rose-50/70 border-rose-200 text-rose-900 opacity-70',
        ];
        $slotHeight = 44; // px на каждые 30 минут
    @endphp

    @if($displayMode === 'calendar' && $viewMode === 'day')
        {{-- КАЛЕНДАРНАЯ СЕТКА: ДЕНЬ ПО ВРАЧАМ --}}
        <div class="bg-white rounded-xl border border-slate-200 overflow-x-auto">
            <div class="min-w-[720px]">
                {{-- Шапка с врачами --}}
                <div class="grid border-b border-slate-200 bg-slate-50"
                     style="grid-template-columns: 76px repeat({{ max(1, $calendarDoctors->count()) }}, minmax(200px, 1fr));">
                    <div class="px-3 py-3 text-xs font-medium text-slate-500 border-r border-slate-200">Время</div>
                    @forelse($calendarDoctors as $doc)
                        <div class="px-4 py-2.5 border-r border-slate-200 last:border-r-0">
                            <div class="font-semibold text-sm text-slate-900 truncate">{{ $doc->name }}</div>
                            @if($doc->specialization)
                                <div class="text-xs text-slate-500 truncate">{{ $doc->specialization }}</div>
                            @endif
                        </div>
                    @empty
                        <div class="px-4 py-3 text-sm text-slate-500">Нет активных врачей</div>
                    @endforelse
                </div>

                {{-- Тело сетки --}}
                <div class="grid relative"
                     style="grid-template-columns: 76px repeat({{ max(1, $calendarDoctors->count()) }}, minmax(200px, 1fr));">
                    {{-- Левая колонка времени --}}
                    <div class="border-r border-slate-200 bg-slate-50/50">
                        @foreach($timeSlots as $slot)
                            <div class="border-b border-slate-100 px-3 flex items-start justify-end pt-1 text-xs {{ str_ends_with($slot, ':00') ? 'font-medium text-slate-700' : 'text-slate-400' }}"
                                 style="height: {{ $slotHeight }}px;">
                                {{ $slot }}
                            </div>
                        @endforeach
                    </div>

                    {{-- Колонки врачей --}}
                    @foreach($calendarDoctors as $doc)
                        @php
                            $docAppointments = $appointments->where('doctor_id', $doc->id);
                        @endphp
                        <div class="relative border-r border-slate-200 last:border-r-0">
                            {{-- Фоновые кликабельные 30-минутные слоты --}}
                            @foreach($timeSlots as $slot)
                                <div
                                    @if($canManage)
                                        wire:click="openCreateModal('{{ $rangeStart->toDateString() }}', '{{ $slot }}', {{ $doc->id }})"
                                    title="Записать к {{ $doc->name }} на {{ $slot }}"
                                    class="border-b border-slate-100 hover:bg-sky-50/60 cursor-pointer transition group relative"
                                    @else
                                        class="border-b border-slate-100"
                                    @endif
                                    style="height: {{ $slotHeight }}px;"
                                >
                                    @if($canManage)
                                        <span class="hidden group-hover:inline-block absolute right-2 top-1 text-[11px] text-sky-600 font-medium">
                                        + {{ $slot }}
                                    </span>
                                    @endif
                                </div>
                            @endforeach

                            {{-- Карточки визитов поверх сетки --}}
                            @foreach($docAppointments as $appt)
                                @php
                                    $startMin = $appt->starts_at->hour * 60 + $appt->starts_at->minute;
                                    $endMin = $appt->ends_at->toDateString() > $appt->starts_at->toDateString()
                                        ? 24 * 60
                                        : ($appt->ends_at->hour * 60 + $appt->ends_at->minute);
                                    $clampedStart = max($dayStartMinutes, min($dayEndMinutes - 15, $startMin));
                                    $clampedEnd = max($clampedStart + 15, min($dayEndMinutes, $endMin));
                                    $topPx = (($clampedStart - $dayStartMinutes) / 30) * $slotHeight;
                                    $heightPx = max(36, (($clampedEnd - $clampedStart) / 30) * $slotHeight - 4);
                                @endphp
                                <div wire:key="cal-day-appt-{{ $appt->id }}"
                                     wire:click.stop="openStatusModal({{ $appt->id }})"
                                     style="top: {{ $topPx + 2 }}px; height: {{ $heightPx }}px;"
                                     class="absolute left-1.5 right-1.5 z-10 rounded-lg border border-l-4 px-2.5 py-1.5 overflow-hidden cursor-pointer shadow-xs transition {{ $cardStyles[$appt->status->value] ?? 'bg-slate-50 border-slate-300' }}">
                                    <div class="flex items-center justify-between gap-1 text-[11px] font-semibold leading-tight">
                                        <span>{{ $appt->starts_at->format('H:i') }}–{{ $appt->ends_at->format('H:i') }}</span>
                                        <span class="truncate opacity-80">{{ $appt->status->label() }}</span>
                                    </div>
                                    <div class="text-xs font-semibold truncate mt-0.5">
                                        {{ $appt->patient?->full_name ?? 'Пациент удален' }}
                                    </div>
                                    @if($heightPx >= 64 && ($appt->deal || $appt->notes))
                                        <div class="text-[11px] opacity-80 truncate mt-0.5">
                                            {{ $appt->deal?->title ?? $appt->notes }}
                                        </div>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    @elseif($displayMode === 'calendar' && $viewMode === 'week')
        {{-- КАЛЕНДАРНАЯ СЕТКА: НЕДЕЛЯ (7 ДНЕЙ) --}}
        <div class="grid grid-cols-1 md:grid-cols-7 gap-3">
            @foreach($weekDays as $day)
                <div wire:key="cal-week-day-{{ $day['date'] }}"
                     class="bg-white rounded-xl border {{ $day['isToday'] ? 'border-sky-400 ring-1 ring-sky-200' : 'border-slate-200' }} flex flex-col min-h-[360px]">
                    <div class="px-3 py-2.5 border-b border-slate-200 flex items-center justify-between {{ $day['isToday'] ? 'bg-sky-50/70' : 'bg-slate-50' }} rounded-t-xl">
                    <span class="text-xs font-semibold {{ $day['isToday'] ? 'text-sky-800' : 'text-slate-700' }}">
                        {{ $day['label'] }}
                    </span>
                        @if($canManage)
                            <button type="button"
                                    wire:click="openCreateModal('{{ $day['date'] }}')"
                                    title="Записать на {{ $day['label'] }}"
                                    class="w-5 h-5 rounded-md bg-white border border-slate-300 text-slate-600 hover:text-sky-600 hover:border-sky-400 text-xs leading-none flex items-center justify-center">
                                +
                            </button>
                        @endif
                    </div>

                    <div class="p-2 space-y-2 flex-1">
                        @forelse($day['appointments'] as $appt)
                            <div wire:key="cal-week-appt-{{ $appt->id }}"
                                 wire:click="openStatusModal({{ $appt->id }})"
                                 class="rounded-lg border border-l-4 p-2 cursor-pointer transition {{ $cardStyles[$appt->status->value] ?? 'bg-slate-50 border-slate-300' }}">
                                <div class="text-[11px] font-semibold">
                                    {{ $appt->starts_at->format('H:i') }}–{{ $appt->ends_at->format('H:i') }}
                                </div>
                                <div class="text-xs font-semibold truncate mt-0.5">
                                    {{ $appt->patient?->full_name ?? 'Пациент удален' }}
                                </div>
                                <div class="text-[11px] opacity-80 truncate">
                                    {{ $appt->doctor?->name }}
                                </div>
                                <div class="text-[10px] mt-1 font-medium opacity-75">
                                    {{ $appt->status->label() }}
                                </div>
                            </div>
                        @empty
                            <div class="text-[11px] text-slate-400 text-center py-6">Нет записей</div>
                        @endforelse
                    </div>
                </div>
            @endforeach
        </div>
    @else
        {{-- ТАБЛИЧНЫЙ ЖУРНАЛ (Список / Напоминания на завтра) --}}
        <div class="bg-white rounded-xl border border-slate-200 overflow-hidden">
            <table class="w-full text-sm text-left">
                <thead class="bg-slate-50 border-b border-slate-200 text-slate-600">
                <tr>
                    <th class="px-5 py-3 font-medium">Дата и время</th>
                    <th class="px-5 py-3 font-medium">Пациент</th>
                    <th class="px-5 py-3 font-medium">Врач</th>
                    <th class="px-5 py-3 font-medium">Сделка / Заметка</th>
                    <th class="px-5 py-3 font-medium">Статус</th>
                    <th class="px-5 py-3 font-medium text-right">Действия</th>
                </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                @forelse($appointments as $appointment)
                    <tr wire:key="appointment-row-{{ $appointment->id }}" class="hover:bg-slate-50/70">
                        <td class="px-5 py-3.5 whitespace-nowrap">
                            <div class="font-semibold text-slate-900">
                                {{ $appointment->starts_at->format('H:i') }} — {{ $appointment->ends_at->format('H:i') }}
                            </div>
                            <div class="text-xs text-slate-500">
                                {{ $appointment->starts_at->format('d.m.Y') }} ({{ $appointment->durationMinutes() }} мин)
                            </div>
                        </td>
                        <td class="px-5 py-3.5">
                            <a href="{{ route('patients.show', $appointment->patient_id) }}"
                               class="font-medium text-slate-900 hover:text-sky-600">
                                {{ $appointment->patient?->full_name ?? 'Пациент удален' }}
                            </a>
                            @if($appointment->patient?->phone)
                                <div class="text-xs text-slate-500 mt-0.5">{{ $appointment->patient->phone }}</div>
                            @endif
                        </td>
                        <td class="px-5 py-3.5 text-slate-700">
                            <div>{{ $appointment->doctor?->name ?? '—' }}</div>
                            @if($appointment->doctor?->specialization)
                                <div class="text-xs text-slate-500">{{ $appointment->doctor->specialization }}</div>
                            @endif
                        </td>
                        <td class="px-5 py-3.5 max-w-xs">
                            @if($appointment->deal)
                                <a href="{{ route('deals.show', $appointment->deal_id) }}"
                                   class="text-xs font-medium text-sky-600 hover:underline block truncate">
                                    {{ $appointment->deal->title }}
                                </a>
                            @endif
                            @if($appointment->notes)
                                <p class="text-xs text-slate-600 mt-0.5 line-clamp-2">{{ $appointment->notes }}</p>
                            @elseif(! $appointment->deal)
                                <span class="text-xs text-slate-400">—</span>
                            @endif
                        </td>
                        <td class="px-5 py-3.5">
                            <span class="inline-flex px-2.5 py-0.5 rounded-full text-xs font-medium {{ $badgeStyles[$appointment->status->value] ?? 'bg-slate-100 text-slate-700' }}">
                                {{ $appointment->status->label() }}
                            </span>
                        </td>
                        <td class="px-5 py-3.5 text-right whitespace-nowrap space-x-2">
                            <button wire:click="openStatusModal({{ $appointment->id }})"
                                    class="text-slate-600 hover:text-slate-900 font-medium">
                                Статус / Заметка
                            </button>
                            @if($canManage)
                                <button wire:click="openEditModal({{ $appointment->id }})"
                                        class="text-sky-600 hover:text-sky-800 font-medium">
                                    Изменить
                                </button>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-5 py-8 text-center text-slate-500">
                            На выбранный период записей нет.
                        </td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>
    @endif

    {{-- Модалка создания / редактирования записи --}}
    @if($showModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/50 p-4 overflow-y-auto">
            <div class="w-full max-w-xl bg-white rounded-2xl shadow-lg border border-slate-200 p-6 my-8">
                <h2 class="text-lg font-semibold text-slate-900 mb-4">
                    {{ $editingId ? 'Редактирование записи' : 'Новая запись на прием' }}
                </h2>

                <form wire:submit="save" class="space-y-4">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1">Пациент</label>
                            <select wire:model.live="patientId" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                                <option value="">Выберите пациента...</option>
                                @foreach($patients as $p)
                                    <option value="{{ $p->id }}">{{ $p->full_name }}</option>
                                @endforeach
                            </select>
                            @error('patientId') <p class="text-xs text-rose-600 mt-1">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1">Врач</label>
                            <select wire:model="doctorId" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                                <option value="">Выберите врача...</option>
                                @foreach($doctors as $doc)
                                    <option value="{{ $doc->id }}">{{ $doc->name }} ({{ $doc->specialization }})</option>
                                @endforeach
                            </select>
                            @error('doctorId') <p class="text-xs text-rose-600 mt-1">{{ $message }}</p> @enderror
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1">Сделка пациента (опционально)</label>
                            <select wire:model="dealId" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                                <option value="">Без привязки к сделке</option>
                                @foreach($patientDeals as $d)
                                    <option value="{{ $d->id }}">{{ $d->title }} ({{ $d->status->label() }})</option>
                                @endforeach
                            </select>
                            @error('dealId') <p class="text-xs text-rose-600 mt-1">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1">Подставить длительность из услуги</label>
                            <select wire:model.live="serviceId" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                                <option value="">Вручную...</option>
                                @foreach($services as $svc)
                                    <option value="{{ $svc->id }}">{{ $svc->name }} ({{ $svc->duration_minutes }} мин)</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1">Дата</label>
                            <input type="date" wire:model="appointmentDate"
                                   class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                            @error('appointmentDate') <p class="text-xs text-rose-600 mt-1">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1">Начало</label>
                            <input type="time" wire:model="startTime"
                                   class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                            @error('startTime') <p class="text-xs text-rose-600 mt-1">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1">Длительность (мин)</label>
                            <input type="number" step="5" min="10" max="480" wire:model="durationMinutes"
                                   class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                            @error('durationMinutes') <p class="text-xs text-rose-600 mt-1">{{ $message }}</p> @enderror
                        </div>
                    </div>

                    @if($editingId)
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1">Статус</label>
                            <select wire:model="status" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                                @foreach($statuses as $st)
                                    <option value="{{ $st->value }}">{{ $st->label() }}</option>
                                @endforeach
                            </select>
                        </div>
                    @endif

                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Заметка к визиту</label>
                        <textarea rows="3" wire:model="notes" placeholder="Этап лечения, пожелания пациента..."
                                  class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm"></textarea>
                        @error('notes') <p class="text-xs text-rose-600 mt-1">{{ $message }}</p> @enderror
                    </div>

                    <div class="flex justify-end gap-3 pt-2">
                        <button type="button" wire:click="$set('showModal', false)"
                                class="px-4 py-2 text-sm rounded-lg border border-slate-300 text-slate-700 hover:bg-slate-50">
                            Отмена
                        </button>
                        <button type="submit"
                                class="px-4 py-2 text-sm rounded-lg bg-sky-600 hover:bg-sky-700 text-white font-medium">
                            Сохранить
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif

    {{-- Модалка смены статуса и заметки (для врача и администратора) --}}
    @if($showStatusModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/50 p-4">
            <div class="w-full max-w-md bg-white rounded-2xl shadow-lg border border-slate-200 p-6">
                <h2 class="text-lg font-semibold text-slate-900 mb-4">Статус визита и заметка</h2>
                @if($activeStatusAppointment)
                    <div class="mb-4 rounded-xl bg-slate-50 border border-slate-200 p-3 text-xs space-y-1.5">
                        <div class="flex items-center justify-between gap-2">
                            <a href="{{ route('patients.show', $activeStatusAppointment->patient_id) }}"
                               class="font-semibold text-sm text-slate-900 hover:text-sky-600">
                                {{ $activeStatusAppointment->patient?->full_name ?? 'Пациент удален' }}
                            </a>
                            <span class="text-slate-500 font-medium">
                                {{ $activeStatusAppointment->starts_at->format('d.m.Y H:i') }}–{{ $activeStatusAppointment->ends_at->format('H:i') }}
                            </span>
                        </div>
                        <div class="text-slate-600">
                            Врач: <strong>{{ $activeStatusAppointment->doctor?->name ?? '—' }}</strong>
                        </div>
                        @if($activeStatusAppointment->deal)
                            <div class="pt-1 border-t border-slate-200/80 flex items-center justify-between gap-2">
                                <span class="text-slate-500">Сделка:</span>
                                <a href="{{ route('deals.show', $activeStatusAppointment->deal_id) }}"
                                   class="font-medium text-sky-600 hover:underline truncate">
                                    {{ $activeStatusAppointment->deal->title }} →
                                </a>
                            </div>
                        @endif
                    </div>
                @endif

                <form wire:submit="saveQuickStatus" class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Статус</label>
                        <select wire:model="quickStatus" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                            @foreach($statuses as $st)
                                <option value="{{ $st->value }}">{{ $st->label() }}</option>
                            @endforeach
                        </select>
                        @error('quickStatus') <p class="text-xs text-rose-600 mt-1">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Заметка по этапу лечения</label>
                        <textarea rows="3" wire:model="quickNotes" placeholder="Например: заменена дуга, следующий визит через 4 недели"
                                  class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm"></textarea>
                        @error('quickNotes') <p class="text-xs text-rose-600 mt-1">{{ $message }}</p> @enderror
                    </div>

                    <div class="flex items-center justify-between gap-3 pt-2">
                        @if($canManage)
                            <button type="button" wire:click="switchToEditFromStatus"
                                    class="text-xs font-medium text-sky-600 hover:text-sky-800">
                                Изменить время / врача
                            </button>
                        @else
                            <span></span>
                        @endif

                        <div class="flex gap-3">
                            <button type="button" wire:click="$set('showStatusModal', false)"
                                    class="px-4 py-2 text-sm rounded-lg border border-slate-300 text-slate-700 hover:bg-slate-50">
                                Отмена
                            </button>
                            <button type="submit"
                                    class="px-4 py-2 text-sm rounded-lg bg-sky-600 hover:bg-sky-700 text-white font-medium">
                                Обновить
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    @endif
</div>
