<div class="space-y-6">
    @if (session('status'))
        <div class="rounded-lg bg-emerald-50 border border-emerald-200 px-4 py-3 text-sm text-emerald-800">
            {{ session('status') }}
        </div>
    @endif

    {{-- Верхняя панель: фильтры, переключатель вида, кнопка создания --}}
    <div class="flex flex-wrap items-center justify-between gap-4">
        <div class="flex flex-wrap items-center gap-3">
            <input type="text" wire:model.live.debounce.300ms="search"
                   placeholder="Поиск по сделке, пациенту, телефону..."
                   class="w-72 rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-sky-500">

            <select wire:model.live="doctorFilter"
                    class="rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-sky-500">
                <option value="">Все врачи</option>
                @foreach($doctors as $doc)
                    <option value="{{ $doc->id }}">{{ $doc->name }}</option>
                @endforeach
            </select>

            <select wire:model.live="installmentFilter"
                    class="rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-sky-500">
                <option value="">Любая оплата</option>
                <option value="yes">Только рассрочка</option>
                <option value="no">Без рассрочки</option>
            </select>

            <select wire:model.live="stageGroupFilter"
                    class="rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-sky-500">
                <option value="all">Все сделки</option>
                <option value="active">Только активные</option>
                <option value="closed">Завершенные и отказы</option>
            </select>

            @if($viewMode === 'table')
                <select wire:model.live="statusFilter"
                        class="rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-sky-500">
                    <option value="">Все этапы</option>
                    @foreach($visibleStatuses as $st)
                        <option value="{{ $st->value }}">{{ $st->label() }}</option>
                    @endforeach
                </select>
            @endif
        </div>

        <div class="flex items-center gap-3">
            <div class="inline-flex rounded-lg border border-slate-300 bg-white p-0.5 text-sm">
                <button type="button" wire:click="setViewMode('kanban')"
                        class="px-3 py-1.5 rounded-md font-medium transition {{ $viewMode === 'kanban' ? 'bg-sky-600 text-white' : 'text-slate-600 hover:text-slate-900' }}">
                    Канбан
                </button>
                <button type="button" wire:click="setViewMode('table')"
                        class="px-3 py-1.5 rounded-md font-medium transition {{ $viewMode === 'table' ? 'bg-sky-600 text-white' : 'text-slate-600 hover:text-slate-900' }}">
                    Таблица
                </button>
            </div>

            @if($canManage)
                <button wire:click="openCreateModal"
                        class="bg-sky-600 hover:bg-sky-700 text-white text-sm font-medium px-4 py-2 rounded-lg transition">
                    + Новая сделка
                </button>
            @endif
        </div>
    </div>

    {{-- Режим КАНБАН --}}
    @if($viewMode === 'kanban')
        @php
            $columnColors = [
                'new' => 'border-t-slate-400',
                'consultation' => 'border-t-sky-500',
                'treatment_plan' => 'border-t-indigo-500',
                'in_progress' => 'border-t-amber-500',
                'completed' => 'border-t-emerald-500',
                'lost' => 'border-t-rose-500',
            ];
        @endphp

        <div class="flex gap-4 overflow-x-auto pb-4">
            @foreach($statuses as $stage)
                @php
                    $stageDeals = $dealsByStatus[$stage->value] ?? collect();
                    $stageSum = $stageDeals->sum(fn ($d) => (float) $d->total_amount);
                @endphp

                <div wire:key="kanban-col-{{ $stage->value }}"
                     class="w-80 flex-shrink-0 bg-slate-100/80 rounded-xl border border-slate-200 border-t-4 {{ $columnColors[$stage->value] ?? 'border-t-slate-400' }} flex flex-col max-h-[75vh]">
                    <div class="p-3.5 border-b border-slate-200/80 bg-white/70 rounded-t-lg">
                        <div class="flex items-center justify-between">
                            <h3 class="font-semibold text-sm text-slate-900">{{ $stage->label() }}</h3>
                            <span class="text-xs font-medium px-2 py-0.5 rounded-full bg-slate-200 text-slate-700">
                            {{ $stageDeals->count() }}
                        </span>
                        </div>
                        <div class="text-xs text-slate-500 mt-1">
                            {{ number_format($stageSum, 0, ',', ' ') }} ₽
                        </div>
                    </div>

                    <div class="p-3 space-y-3 overflow-y-auto flex-1 min-h-[120px]"
                         data-status="{{ $stage->value }}"
                         @if($canManage)
                             x-data
                         x-init="
                             if (window.Sortable && !$el._sortable) {
                                 $el._sortable = Sortable.create($el, {
                                     group: 'deals-kanban',
                                     animation: 150,
                                     draggable: '[data-deal-id]',
                                     filter: 'select, button, a',
                                     preventOnFilter: false,
                                     ghostClass: 'opacity-40',
                                     onUpdate: (evt) => {
                                         const ids = Array.from(evt.to.querySelectorAll('[data-deal-id]')).map(el => Number(el.dataset.dealId));
                                         $wire.syncColumnOrder(evt.to.dataset.status, ids, Number(evt.item.dataset.dealId));
                                     },
                                     onAdd: (evt) => {
                                         const ids = Array.from(evt.to.querySelectorAll('[data-deal-id]')).map(el => Number(el.dataset.dealId));
                                         $wire.syncColumnOrder(evt.to.dataset.status, ids, Number(evt.item.dataset.dealId));
                                     }
                                 });
                             }
                         "
                        @endif
                    >
                        @forelse($stageDeals as $deal)
                            <div wire:key="kanban-deal-{{ $deal->id }}"
                                 data-deal-id="{{ $deal->id }}"
                                 class="bg-white rounded-xl border border-slate-200 p-3.5 shadow-xs hover:border-sky-300 transition {{ $canManage ? 'cursor-grab active:cursor-grabbing' : '' }}">
                                <div class="flex items-start justify-between gap-2">
                                    <a href="{{ route('deals.show', $deal->id) }}"
                                       class="font-medium text-sm text-slate-900 hover:text-sky-600 leading-snug">
                                        {{ $deal->title }}
                                    </a>
                                    @if($canManage)
                                        <button wire:click="openEditModal({{ $deal->id }})"
                                                class="text-xs text-slate-400 hover:text-sky-600 flex-shrink-0"
                                                title="Редактировать">
                                            Изм.
                                        </button>
                                    @endif
                                </div>

                                <div class="mt-2 text-xs text-slate-600">
                                    <a href="{{ route('patients.show', $deal->patient_id) }}" class="hover:text-sky-600">
                                        {{ $deal->patient?->full_name ?? 'Пациент удален' }}
                                    </a>
                                </div>

                                @if($deal->doctor)
                                    <div class="mt-1 text-xs text-slate-500">
                                        Врач: {{ $deal->doctor->name }}
                                    </div>
                                @endif

                                <div class="mt-3 flex flex-wrap items-center justify-between gap-2 pt-2.5 border-t border-slate-100">
                                <span class="text-sm font-semibold text-slate-900">
                                    {{ number_format((float) $deal->total_amount, 0, ',', ' ') }} ₽
                                </span>

                                    <div class="flex items-center gap-1.5">
                                        @if($deal->is_installment)
                                            <span class="px-2 py-0.5 rounded-full text-[11px] font-medium {{ $deal->is_overdue ? 'bg-rose-100 text-rose-700' : 'bg-amber-50 text-amber-700' }}">
                                            {{ $deal->is_overdue ? 'Просрочка' : 'Рассрочка' }}
                                        </span>
                                        @endif
                                    </div>
                                </div>

                                @if($deal->status === \Modules\Deals\Enums\DealStatus::Lost && $deal->loss_reason)
                                    <div class="mt-2 text-[11px] text-rose-700 bg-rose-50 rounded-md px-2 py-1">
                                        Причина: {{ $deal->loss_reason->label() }}
                                    </div>
                                @endif

                                @if($canManage)
                                    <div class="mt-2.5">
                                        <select aria-label="Сменить этап"
                                                wire:change="moveStage({{ $deal->id }}, $event.target.value)"
                                                class="w-full rounded-md border border-slate-200 bg-slate-50 px-2 py-1 text-xs text-slate-700 focus:outline-none focus:ring-1 focus:ring-sky-500">
                                            @foreach($statuses as $opt)
                                                <option value="{{ $opt->value }}" @selected($deal->status === $opt)>
                                                    {{ $opt->label() }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                @endif
                            </div>
                        @empty
                            <div class="text-xs text-slate-400 text-center py-6 border border-dashed border-slate-300 rounded-lg">
                                Нет сделок
                            </div>
                        @endforelse
                    </div>
                </div>
            @endforeach
        </div>
    @else
        {{-- Режим ТАБЛИЦА --}}
        <div class="bg-white rounded-xl border border-slate-200 overflow-hidden">
            <table class="w-full text-sm text-left">
                <thead class="bg-slate-50 border-b border-slate-200 text-slate-600">
                <tr>
                    <th class="px-5 py-3 font-medium">Сделка / Пациент</th>
                    <th class="px-5 py-3 font-medium">Врач</th>
                    <th class="px-5 py-3 font-medium">Этап</th>
                    <th class="px-5 py-3 font-medium">Сумма</th>
                    <th class="px-5 py-3 font-medium">Оплата</th>
                    <th class="px-5 py-3 font-medium text-right">Действия</th>
                </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                @forelse($deals as $deal)
                    <tr class="hover:bg-slate-50/70">
                        <td class="px-5 py-3.5">
                            <a href="{{ route('deals.show', $deal->id) }}" class="font-medium text-slate-900 hover:text-sky-600">
                                {{ $deal->title }}
                            </a>
                            <div class="text-xs text-slate-500 mt-0.5">
                                {{ $deal->patient?->full_name }}
                            </div>
                        </td>
                        <td class="px-5 py-3.5 text-slate-700">
                            {{ $deal->doctor?->name ?? 'Не назначен' }}
                        </td>
                        <td class="px-5 py-3.5">
                                <span class="inline-flex px-2.5 py-0.5 rounded-full text-xs font-medium bg-slate-100 text-slate-700">
                                    {{ $deal->status->label() }}
                                </span>
                            @if($deal->loss_reason)
                                <div class="text-xs text-rose-600 mt-0.5">{{ $deal->loss_reason->label() }}</div>
                            @endif
                        </td>
                        <td class="px-5 py-3.5 font-semibold text-slate-900">
                            {{ number_format((float) $deal->total_amount, 0, ',', ' ') }} ₽
                        </td>
                        <td class="px-5 py-3.5">
                            @if($deal->is_installment)
                                <span class="inline-flex px-2.5 py-0.5 rounded-full text-xs font-medium bg-amber-50 text-amber-700">
                                        Рассрочка
                                    </span>
                                @if($deal->next_payment_date)
                                    <div class="text-xs text-slate-500 mt-0.5">
                                        до {{ $deal->next_payment_date->format('d.m.Y') }}
                                    </div>
                                @endif
                            @else
                                <span class="text-xs text-slate-500">Полная</span>
                            @endif
                        </td>
                        <td class="px-5 py-3.5 text-right space-x-2">
                            <a href="{{ route('deals.show', $deal->id) }}" class="text-slate-600 hover:text-slate-900 font-medium">
                                Открыть
                            </a>
                            @if($canManage)
                                <button wire:click="openEditModal({{ $deal->id }})" class="text-sky-600 hover:text-sky-800 font-medium">
                                    Изменить
                                </button>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-5 py-8 text-center text-slate-500">
                            Сделки не найдены.
                        </td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>
    @endif

    {{-- Модалка создания / редактирования сделки --}}
    @if($showModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/50 p-4 overflow-y-auto">
            <div class="w-full max-w-2xl bg-white rounded-2xl shadow-lg border border-slate-200 p-6 my-8">
                <h2 class="text-lg font-semibold text-slate-900 mb-4">
                    {{ $editingId ? 'Редактирование сделки' : 'Новая сделка' }}
                </h2>

                <form wire:submit="save" class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Название сделки</label>
                        <input type="text" wire:model="title" placeholder="Напр. Установка металлической брекет-системы"
                               class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                        @error('title') <p class="text-xs text-rose-600 mt-1">{{ $message }}</p> @enderror
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1">Пациент</label>
                            <select wire:model="patientId" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                                <option value="">Выберите пациента...</option>
                                @foreach($patients as $p)
                                    <option value="{{ $p->id }}">{{ $p->full_name }} ({{ $p->phone }})</option>
                                @endforeach
                            </select>
                            @error('patientId') <p class="text-xs text-rose-600 mt-1">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1">Ответственный врач</label>
                            <select wire:model="doctorId" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                                <option value="">Не назначен</option>
                                @foreach($doctors as $doc)
                                    <option value="{{ $doc->id }}">{{ $doc->name }} ({{ $doc->specialization }})</option>
                                @endforeach
                            </select>
                            @error('doctorId') <p class="text-xs text-rose-600 mt-1">{{ $message }}</p> @enderror
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1">Этап воронки</label>
                            <select wire:model.live="status" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                                @foreach($statuses as $st)
                                    <option value="{{ $st->value }}">{{ $st->label() }}</option>
                                @endforeach
                            </select>
                            @error('status') <p class="text-xs text-rose-600 mt-1">{{ $message }}</p> @enderror
                        </div>

                        @if($status === 'lost')
                            <div>
                                <label class="block text-sm font-medium text-rose-700 mb-1">Причина отказа *</label>
                                <select wire:model="lossReason" class="w-full rounded-lg border border-rose-300 px-3 py-2 text-sm">
                                    <option value="">Выберите причину...</option>
                                    @foreach($lossReasons as $reason)
                                        <option value="{{ $reason->value }}">{{ $reason->label() }}</option>
                                    @endforeach
                                </select>
                                @error('lossReason') <p class="text-xs text-rose-600 mt-1">{{ $message }}</p> @enderror
                            </div>
                        @endif
                    </div>

                    {{-- Состав услуг из Catalog --}}
                    <div class="border border-slate-200 rounded-xl p-4 bg-slate-50/60 space-y-3">
                        <div class="flex items-center justify-between">
                            <span class="text-sm font-semibold text-slate-800">Услуги по плану лечения</span>
                            <button type="button" wire:click="addItemRow"
                                    class="text-xs font-medium text-sky-600 hover:text-sky-800">
                                + Добавить услугу
                            </button>
                        </div>

                        @foreach($items as $index => $row)
                            <div wire:key="deal-item-row-{{ $index }}" class="grid grid-cols-12 gap-2 items-center">
                                <div class="col-span-6">
                                    <select wire:model.live="items.{{ $index }}.service_id"
                                            class="w-full rounded-lg border border-slate-300 bg-white px-2.5 py-1.5 text-sm">
                                        <option value="">Выберите услугу...</option>
                                        @foreach($services as $svc)
                                            <option value="{{ $svc->id }}">
                                                {{ $svc->name }} ({{ number_format((float) $svc->price, 0, ',', ' ') }} ₽)
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-span-2">
                                    <input type="number" min="1" max="100"
                                           wire:model.live="items.{{ $index }}.quantity"
                                           placeholder="Кол-во"
                                           class="w-full rounded-lg border border-slate-300 bg-white px-2.5 py-1.5 text-sm">
                                </div>
                                <div class="col-span-3">
                                    <input type="number" step="0.01" min="0"
                                           wire:model.live="items.{{ $index }}.unit_price"
                                           placeholder="Цена ₽"
                                           class="w-full rounded-lg border border-slate-300 bg-white px-2.5 py-1.5 text-sm">
                                </div>
                                <div class="col-span-1 text-right">
                                    <button type="button" wire:click="removeItemRow({{ $index }})"
                                            class="text-slate-400 hover:text-rose-600 text-sm font-bold px-1">
                                        ×
                                    </button>
                                </div>
                            </div>
                        @endforeach

                        <div class="flex justify-end pt-2 border-t border-slate-200 text-sm">
                            <span class="text-slate-600 mr-2">Итоговая сумма:</span>
                            <span class="font-bold text-slate-900">
                                {{ number_format($this->calculatedTotal, 0, ',', ' ') }} ₽
                            </span>
                        </div>
                    </div>

                    {{-- Рассрочка --}}
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 items-center">
                        <label class="flex items-center gap-2 text-sm text-slate-700">
                            <input type="checkbox" wire:model.live="isInstallment" class="rounded border-slate-300">
                            Оплата в рассрочку
                        </label>

                        @if($isInstallment)
                            <div>
                                <label class="block text-xs font-medium text-slate-600 mb-1">Дата следующего платежа</label>
                                <input type="date" wire:model="nextPaymentDate"
                                       class="w-full rounded-lg border border-slate-300 px-3 py-1.5 text-sm">
                                @error('nextPaymentDate') <p class="text-xs text-rose-600 mt-1">{{ $message }}</p> @enderror
                            </div>
                        @endif
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

    {{-- Модалка обязательной причины отказа --}}
    @if($showLossModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/50 p-4">
            <div class="w-full max-w-md bg-white rounded-2xl shadow-lg border border-slate-200 p-6">
                <h2 class="text-lg font-semibold text-slate-900 mb-2">Укажите причину отказа</h2>
                <p class="text-sm text-slate-500 mb-4">
                    Для аналитики воронки закрытие сделки в статус «Отказ» требует обязательного указания причины.
                </p>

                <form wire:submit="confirmLoss" class="space-y-4">
                    <div>
                        <select wire:model="pendingLossReason"
                                class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                            <option value="">Выберите причину...</option>
                            @foreach($lossReasons as $reason)
                                <option value="{{ $reason->value }}">{{ $reason->label() }}</option>
                            @endforeach
                        </select>
                        @error('pendingLossReason') <p class="text-xs text-rose-600 mt-1">{{ $message }}</p> @enderror
                    </div>

                    <div class="flex justify-end gap-3">
                        <button type="button" wire:click="$set('showLossModal', false)"
                                class="px-4 py-2 text-sm rounded-lg border border-slate-300 text-slate-700 hover:bg-slate-50">
                            Отмена
                        </button>
                        <button type="submit"
                                class="px-4 py-2 text-sm rounded-lg bg-rose-600 hover:bg-rose-700 text-white font-medium">
                            Подтвердить отказ
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif
</div>
