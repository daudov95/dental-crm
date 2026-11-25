<div class="space-y-6">
    {{-- Заголовок и экспорт --}}
    <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-slate-900">Аналитика и KPI руководителя</h1>
            <p class="text-sm text-slate-500 mt-1">
                Сводка за период {{ $summary->period->from->format('d.m.Y') }} — {{ $summary->period->to->format('d.m.Y') }}
            </p>
        </div>

        <a href="{{ route('analytics.export', ['from' => $dateFrom, 'to' => $dateTo]) }}"
           class="inline-flex items-center justify-center gap-2 px-4 py-2 text-sm font-medium text-white bg-emerald-600 hover:bg-emerald-700 rounded-lg transition">
            Скачать отчет (CSV / Excel)
        </a>
    </div>

    {{-- Фильтр периода и быстрые пресеты --}}
    <div class="bg-white rounded-xl border border-slate-200 p-4 flex flex-col lg:flex-row lg:items-end justify-between gap-4">
        <div class="flex flex-wrap items-center gap-2">
            <button type="button" wire:click="setPreset('today')"
                    class="px-3 py-1.5 text-xs font-medium rounded-lg border border-slate-300 text-slate-700 hover:bg-slate-50">
                Сегодня
            </button>
            <button type="button" wire:click="setPreset('week')"
                    class="px-3 py-1.5 text-xs font-medium rounded-lg border border-slate-300 text-slate-700 hover:bg-slate-50">
                7 дней
            </button>
            <button type="button" wire:click="setPreset('month')"
                    class="px-3 py-1.5 text-xs font-medium rounded-lg border border-slate-300 text-slate-700 hover:bg-slate-50">
                Текущий месяц
            </button>
            <button type="button" wire:click="setPreset('quarter')"
                    class="px-3 py-1.5 text-xs font-medium rounded-lg border border-slate-300 text-slate-700 hover:bg-slate-50">
                3 месяца
            </button>
            <button type="button" wire:click="setPreset('year')"
                    class="px-3 py-1.5 text-xs font-medium rounded-lg border border-slate-300 text-slate-700 hover:bg-slate-50">
                С начала года
            </button>
        </div>

        <div class="flex items-center gap-3">
            <div>
                <label class="block text-xs font-medium text-slate-500 mb-1">С даты</label>
                <input type="date" wire:model.live="dateFrom"
                       class="rounded-lg border border-slate-300 px-3 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-sky-500">
            </div>
            <div>
                <label class="block text-xs font-medium text-slate-500 mb-1">По дату</label>
                <input type="date" wire:model.live="dateTo"
                       class="rounded-lg border border-slate-300 px-3 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-sky-500">
            </div>
        </div>
    </div>

    {{-- Карточки KPI --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-5 gap-4">
        <div class="bg-white rounded-xl border border-slate-200 p-4">
            <div class="text-xs font-medium text-slate-500">Новые заявки (сделки)</div>
            <div class="text-2xl font-bold text-slate-900 mt-1">{{ $summary->newDealsCount }}</div>
            <div class="text-xs text-slate-500 mt-1">Новых пациентов: {{ $summary->newPatientsCount }}</div>
        </div>

        <div class="bg-white rounded-xl border border-slate-200 p-4">
            <div class="text-xs font-medium text-slate-500">Конверсия в лечение</div>
            <div class="text-2xl font-bold text-sky-600 mt-1">{{ number_format($summary->conversionRate, 1, ',', ' ') }}%</div>
            <div class="text-xs text-slate-500 mt-1">{{ $summary->convertedDealsCount }} из {{ $summary->newDealsCount }} сделок</div>
        </div>

        <div class="bg-white rounded-xl border border-slate-200 p-4">
            <div class="text-xs font-medium text-slate-500">Выручка за период</div>
            <div class="text-2xl font-bold text-emerald-600 mt-1">{{ number_format($summary->revenue, 0, ',', ' ') }} ₽</div>
            <div class="text-xs text-slate-500 mt-1">Платежей: {{ $summary->paymentsCount }}</div>
        </div>

        <div class="bg-white rounded-xl border border-slate-200 p-4">
            <div class="text-xs font-medium text-slate-500">Дебиторская задолженность</div>
            <div class="text-2xl font-bold text-amber-600 mt-1">{{ number_format($summary->totalReceivables, 0, ',', ' ') }} ₽</div>
            <div class="text-xs {{ $summary->overdueDealsCount > 0 ? 'text-rose-600 font-medium' : 'text-slate-500' }} mt-1">
                Просроченных рассрочек: {{ $summary->overdueDealsCount }}
            </div>
        </div>

        <div class="bg-white rounded-xl border border-slate-200 p-4">
            <div class="text-xs font-medium text-slate-500">Процент неявок</div>
            <div class="text-2xl font-bold {{ $summary->noShowRate > 15 ? 'text-rose-600' : 'text-slate-900' }} mt-1">
                {{ number_format($summary->noShowRate, 1, ',', ' ') }}%
            </div>
            <div class="text-xs text-slate-500 mt-1">
                Неявок: {{ $summary->noShowVisitsCount }} / Пришли: {{ $summary->completedVisitsCount }}
            </div>
        </div>
    </div>

    {{-- Воронка сделок и Каналы привлечения --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <div class="bg-white rounded-xl border border-slate-200 p-5 space-y-4">
            <h2 class="text-base font-semibold text-slate-900">Воронка сделок за период</h2>
            <div class="space-y-3">
                @foreach($summary->funnel as $stage)
                    <div>
                        <div class="flex items-center justify-between text-xs mb-1">
                            <span class="font-medium text-slate-700">{{ $stage['label'] }}</span>
                            <span class="text-slate-600">
                                <strong>{{ $stage['count'] }}</strong> · {{ number_format($stage['amount'], 0, ',', ' ') }} ₽ ({{ $stage['share'] }}%)
                            </span>
                        </div>
                        <div class="w-full bg-slate-100 rounded-full h-2 overflow-hidden">
                            <div class="bg-sky-500 h-2 rounded-full" style="width: {{ min(100, $stage['share']) }}%"></div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        <div class="bg-white rounded-xl border border-slate-200 p-5 space-y-4">
            <h2 class="text-base font-semibold text-slate-900">Каналы привлечения пациентов</h2>
            <div class="space-y-3">
                @foreach($summary->sources as $source)
                    <div>
                        <div class="flex items-center justify-between text-xs mb-1">
                            <span class="font-medium text-slate-700">{{ $source['label'] }}</span>
                            <span class="text-slate-600">
                                <strong>{{ $source['count'] }}</strong> ({{ $source['share'] }}%)
                            </span>
                        </div>
                        <div class="w-full bg-slate-100 rounded-full h-2 overflow-hidden">
                            <div class="bg-emerald-500 h-2 rounded-full" style="width: {{ min(100, $source['share']) }}%"></div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>

    {{-- Причины отказов и Способы оплаты --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <div class="bg-white rounded-xl border border-slate-200 p-5 space-y-4">
            <h2 class="text-base font-semibold text-slate-900">Анализ причин отказов</h2>
            @if($summary->lossReasons === [])
                <p class="text-sm text-slate-500">За выбранный период отказов по сделкам не зафиксировано.</p>
            @else
                <div class="space-y-3">
                    @foreach($summary->lossReasons as $item)
                        <div>
                            <div class="flex items-center justify-between text-xs mb-1">
                                <span class="font-medium text-slate-700">{{ $item['label'] }}</span>
                                <span class="text-slate-600">
                                    <strong>{{ $item['count'] }}</strong> ({{ $item['share'] }}%)
                                </span>
                            </div>
                            <div class="w-full bg-slate-100 rounded-full h-2 overflow-hidden">
                                <div class="bg-rose-500 h-2 rounded-full" style="width: {{ min(100, $item['share']) }}%"></div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>

        <div class="bg-white rounded-xl border border-slate-200 p-5 space-y-4">
            <h2 class="text-base font-semibold text-slate-900">Структура выручки по способам оплаты</h2>
            <div class="space-y-3">
                @foreach($summary->paymentMethods as $pm)
                    <div>
                        <div class="flex items-center justify-between text-xs mb-1">
                            <span class="font-medium text-slate-700">{{ $pm['label'] }}</span>
                            <span class="text-slate-600">
                                <strong>{{ number_format($pm['amount'], 0, ',', ' ') }} ₽</strong> · {{ $pm['count'] }} опл. ({{ $pm['share'] }}%)
                            </span>
                        </div>
                        <div class="w-full bg-slate-100 rounded-full h-2 overflow-hidden">
                            <div class="bg-amber-500 h-2 rounded-full" style="width: {{ min(100, $pm['share']) }}%"></div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>

    {{-- Таблица показателей по врачам --}}
    <div class="bg-white rounded-xl border border-slate-200 overflow-hidden">
        <div class="px-5 py-4 border-b border-slate-200">
            <h2 class="text-base font-semibold text-slate-900">Загрузка и показатели врачей за период</h2>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 text-sm">
                <thead class="bg-slate-50 text-slate-600 text-xs uppercase tracking-wider">
                <tr>
                    <th class="px-4 py-3 text-left font-medium">Врач</th>
                    <th class="px-4 py-3 text-left font-medium">Специализация</th>
                    <th class="px-4 py-3 text-right font-medium">Проведено визитов</th>
                    <th class="px-4 py-3 text-right font-medium">Неявки</th>
                    <th class="px-4 py-3 text-right font-medium">Активных сделок</th>
                    <th class="px-4 py-3 text-right font-medium">Сумма сделок</th>
                </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                @forelse($summary->doctors as $doc)
                    <tr class="hover:bg-slate-50/70">
                        <td class="px-4 py-3 font-medium text-slate-900">{{ $doc['doctor_name'] }}</td>
                        <td class="px-4 py-3 text-slate-500">{{ $doc['specialization'] ?? '—' }}</td>
                        <td class="px-4 py-3 text-right text-slate-700">{{ $doc['completed_visits'] }}</td>
                        <td class="px-4 py-3 text-right {{ $doc['no_show_visits'] > 0 ? 'text-rose-600 font-medium' : 'text-slate-500' }}">
                            {{ $doc['no_show_visits'] }}
                        </td>
                        <td class="px-4 py-3 text-right text-slate-700">{{ $doc['deals_count'] }}</td>
                        <td class="px-4 py-3 text-right font-medium text-slate-900">
                            {{ number_format($doc['deals_amount'], 0, ',', ' ') }} ₽
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-4 py-6 text-center text-slate-500">Врачи не найдены.</td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
