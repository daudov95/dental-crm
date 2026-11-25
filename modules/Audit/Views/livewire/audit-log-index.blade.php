<div class="space-y-6">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-slate-900">Журнал аудита (152-ФЗ)</h1>
            <p class="text-sm text-slate-500 mt-1">
                Автоматическая фиксация операций с персональными данными пациентов, сделками, визитами и оплатами.
            </p>
        </div>
        <button type="button"
                wire:click="resetFilters"
                class="inline-flex items-center px-3 py-2 text-sm font-medium text-slate-700 bg-white border border-slate-300 rounded-lg hover:bg-slate-50 transition">
            Сбросить фильтры
        </button>
    </div>

    {{-- Фильтры --}}
    <div class="bg-white rounded-xl border border-slate-200 p-4 grid grid-cols-1 md:grid-cols-3 lg:grid-cols-6 gap-3">
        <div class="lg:col-span-2">
            <label class="block text-xs font-medium text-slate-500 mb-1">Поиск (сотрудник, IP, ID, детали)</label>
            <input type="text"
                   wire:model.live.debounce.300ms="search"
                   placeholder="Например: 127.0.0.1 или Магомадов..."
                   class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-sky-500">
        </div>

        <div>
            <label class="block text-xs font-medium text-slate-500 mb-1">Событие</label>
            <select wire:model.live="actionFilter"
                    class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-sky-500">
                <option value="">Все события</option>
                @foreach($actions as $action)
                    <option value="{{ $action->value }}">{{ $action->label() }}</option>
                @endforeach
            </select>
        </div>

        <div>
            <label class="block text-xs font-medium text-slate-500 mb-1">Раздел</label>
            <select wire:model.live="entityFilter"
                    class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-sky-500">
                <option value="">Все разделы</option>
                @foreach($entities as $class => $label)
                    <option value="{{ $class }}">{{ $label }}</option>
                @endforeach
            </select>
        </div>

        <div>
            <label class="block text-xs font-medium text-slate-500 mb-1">Сотрудник</label>
            <select wire:model.live="userFilter"
                    class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-sky-500">
                <option value="">Все сотрудники</option>
                @foreach($users as $user)
                    <option value="{{ $user->id }}">{{ $user->name }}</option>
                @endforeach
            </select>
        </div>

        <div class="grid grid-cols-2 gap-2">
            <div>
                <label class="block text-xs font-medium text-slate-500 mb-1">С</label>
                <input type="date"
                       wire:model.live="dateFrom"
                       class="w-full rounded-lg border border-slate-300 px-2 py-2 text-xs focus:outline-none focus:ring-2 focus:ring-sky-500">
            </div>
            <div>
                <label class="block text-xs font-medium text-slate-500 mb-1">По</label>
                <input type="date"
                       wire:model.live="dateTo"
                       class="w-full rounded-lg border border-slate-300 px-2 py-2 text-xs focus:outline-none focus:ring-2 focus:ring-sky-500">
            </div>
        </div>
    </div>

    {{-- Таблица журнала --}}
    <div class="bg-white rounded-xl border border-slate-200 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 text-sm">
                <thead class="bg-slate-50 text-slate-600 text-xs uppercase tracking-wider">
                <tr>
                    <th class="px-4 py-3 text-left font-medium">Дата и время</th>
                    <th class="px-4 py-3 text-left font-medium">Сотрудник</th>
                    <th class="px-4 py-3 text-left font-medium">Событие</th>
                    <th class="px-4 py-3 text-left font-medium">Объект</th>
                    <th class="px-4 py-3 text-left font-medium">IP-адрес</th>
                    <th class="px-4 py-3 text-right font-medium">Детали</th>
                </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                @forelse($logs as $log)
                    <tr class="hover:bg-slate-50/70">
                        <td class="px-4 py-3 whitespace-nowrap text-slate-700 font-mono text-xs">
                            {{ $log->created_at?->format('d.m.Y H:i:s') }}
                        </td>
                        <td class="px-4 py-3">
                            @if($log->user)
                                <div class="font-medium text-slate-900">{{ $log->user->name }}</div>
                                <div class="text-xs text-slate-500">{{ $log->user->role->label() }}</div>
                            @else
                                <span class="text-slate-400 italic">Система</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 whitespace-nowrap">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium border {{ $log->action->badgeClass() }}">
                                    {{ $log->action->label() }}
                                </span>
                        </td>
                        <td class="px-4 py-3 whitespace-nowrap text-slate-700">
                            <span class="font-medium">{{ $log->entityLabel() }}</span>
                            <span class="text-slate-400">#{{ $log->auditable_id }}</span>
                        </td>
                        <td class="px-4 py-3 whitespace-nowrap font-mono text-xs text-slate-500">
                            {{ $log->ip_address ?? '—' }}
                        </td>
                        <td class="px-4 py-3 whitespace-nowrap text-right">
                            <button type="button"
                                    wire:click="inspectLog({{ $log->id }})"
                                    class="text-sky-600 hover:text-sky-800 text-xs font-medium">
                                Подробнее
                            </button>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-4 py-10 text-center text-slate-500">
                            Записей аудита по выбранным условиям не найдено.
                        </td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>

        @if($logs->hasPages())
            <div class="px-4 py-3 border-t border-slate-200">
                {{ $logs->links() }}
            </div>
        @endif
    </div>

    {{-- Модалка просмотра метаданных --}}
    @if($selectedLog)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/50 p-4">
            <div class="bg-white rounded-2xl shadow-xl border border-slate-200 w-full max-w-xl overflow-hidden">
                <div class="px-6 py-4 border-b border-slate-200 flex items-center justify-between">
                    <div>
                        <h3 class="text-base font-semibold text-slate-900">
                            Событие #{{ $selectedLog->id }} — {{ $selectedLog->action->label() }}
                        </h3>
                        <p class="text-xs text-slate-500 mt-0.5">
                            {{ $selectedLog->created_at?->format('d.m.Y H:i:s') }} · IP: {{ $selectedLog->ip_address ?? '—' }}
                        </p>
                    </div>
                    <button type="button" wire:click="closeModal" class="text-slate-400 hover:text-slate-600 text-xl leading-none">
                        &times;
                    </button>
                </div>

                <div class="p-6 space-y-4">
                    <div class="grid grid-cols-2 gap-3 text-xs">
                        <div class="bg-slate-50 rounded-lg p-3">
                            <div class="text-slate-500">Сотрудник</div>
                            <div class="font-medium text-slate-900 mt-0.5">{{ $selectedLog->user?->name ?? 'Система' }}</div>
                        </div>
                        <div class="bg-slate-50 rounded-lg p-3">
                            <div class="text-slate-500">Сущность</div>
                            <div class="font-medium text-slate-900 mt-0.5">
                                {{ $selectedLog->entityLabel() }} #{{ $selectedLog->auditable_id }}
                            </div>
                        </div>
                    </div>

                    <div>
                        <div class="text-xs font-medium text-slate-500 mb-2">Детали операции</div>
                        @if($selectedLogDetails !== [])
                            <div class="rounded-xl border border-slate-200 overflow-hidden">
                                <dl class="divide-y divide-slate-100 text-xs">
                                    @foreach($selectedLogDetails as $row)
                                        <div class="grid grid-cols-3 gap-3 px-4 py-2.5 bg-white">
                                            <dt class="text-slate-500 font-medium">{{ $row['label'] }}</dt>
                                            <dd class="col-span-2 text-slate-900 font-medium break-words">{{ $row['value'] }}</dd>
                                        </div>
                                    @endforeach
                                </dl>
                            </div>
                        @else
                            <div class="text-xs text-slate-500 bg-slate-50 rounded-lg p-3">
                                Дополнительные параметры отсутствуют.
                            </div>
                        @endif
                    </div>

                    <details class="group">
                        <summary class="cursor-pointer select-none text-xs font-medium text-slate-500 hover:text-slate-800">
                            Показать исходный JSON (JSONB)
                        </summary>
                        <pre class="mt-2 bg-slate-900 text-slate-100 rounded-xl p-4 text-xs overflow-x-auto max-h-60">{{ json_encode($selectedLog->metadata ?? [], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                    </details>
                </div>

                <div class="px-6 py-3 bg-slate-50 border-t border-slate-200 flex justify-end">
                    <button type="button"
                            wire:click="closeModal"
                            class="px-4 py-2 text-sm font-medium text-slate-700 bg-white border border-slate-300 rounded-lg hover:bg-slate-100">
                        Закрыть
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>
