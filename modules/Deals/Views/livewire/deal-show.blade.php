<div class="space-y-6">
    <div>
        <a href="{{ route('deals.index') }}" class="text-sm text-sky-600 hover:text-sky-800 font-medium">
            ← Вернуться к воронке сделок
        </a>
    </div>

    @if (session('status'))
        <div class="rounded-lg bg-emerald-50 border border-emerald-200 px-4 py-3 text-sm text-emerald-800">
            {{ session('status') }}
        </div>
    @endif

    {{-- Шапка сделки --}}
    <div class="bg-white rounded-xl border border-slate-200 p-6">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div>
                <h2 class="text-xl font-bold text-slate-900">{{ $deal->title }}</h2>
                <div class="mt-2 flex flex-wrap items-center gap-3 text-sm text-slate-600">
                    <span>
                        Пациент:
                        <a href="{{ route('patients.show', $deal->patient_id) }}" class="text-sky-600 hover:underline font-medium">
                            {{ $deal->patient?->full_name ?? 'Не указан' }}
                        </a>
                    </span>
                    <span>•</span>
                    <span>Врач: <strong>{{ $deal->doctor?->name ?? 'Не назначен' }}</strong></span>
                </div>
            </div>

            <div class="text-right">
                <div class="text-2xl font-bold text-slate-900">
                    {{ number_format((float) $deal->total_amount, 0, ',', ' ') }} ₽
                </div>
                <div class="mt-1 flex justify-end gap-2">
                    <span class="inline-flex px-2.5 py-0.5 rounded-full text-xs font-medium bg-sky-50 text-sky-700">
                        {{ $deal->status->label() }}
                    </span>
                    @if($deal->is_installment)
                        <span class="inline-flex px-2.5 py-0.5 rounded-full text-xs font-medium {{ $deal->is_overdue ? 'bg-rose-100 text-rose-700' : 'bg-amber-50 text-amber-700' }}">
                            {{ $deal->is_overdue ? 'Просрочка рассрочки' : 'Рассрочка' }}
                        </span>
                    @endif
                </div>
            </div>
        </div>

        @if($deal->status === \Modules\Deals\Enums\DealStatus::Lost && $deal->loss_reason)
            <div class="mt-4 rounded-lg bg-rose-50 border border-rose-200 px-4 py-2.5 text-sm text-rose-800">
                Причина отказа: <strong>{{ $deal->loss_reason->label() }}</strong>
            </div>
        @endif

        @if($deal->is_installment && $deal->next_payment_date)
            <div class="mt-4 text-sm text-slate-600">
                Дата следующего платежа по рассрочке: <strong>{{ $deal->next_payment_date->format('d.m.Y') }}</strong>
            </div>
        @endif

        @if($canManage)
            <div class="mt-5 pt-4 border-t border-slate-100 flex flex-wrap items-center gap-2">
                <span class="text-xs font-medium text-slate-500 mr-2">Перевести на этап:</span>
                @foreach($statuses as $st)
                    <button type="button"
                            wire:click="moveStage('{{ $st->value }}')"
                            @disabled($deal->status === $st)
                            class="px-3 py-1.5 rounded-lg text-xs font-medium transition {{ $deal->status === $st ? 'bg-sky-600 text-white cursor-default' : 'bg-slate-100 text-slate-700 hover:bg-slate-200' }}">
                        {{ $st->label() }}
                    </button>
                @endforeach
            </div>
        @endif
    </div>

    {{-- Состав услуг --}}
    <div class="bg-white rounded-xl border border-slate-200 overflow-hidden">
        <div class="px-6 py-4 border-b border-slate-200">
            <h3 class="font-semibold text-slate-900">Состав плана лечения (услуги)</h3>
        </div>
        <table class="w-full text-sm text-left">
            <thead class="bg-slate-50 border-b border-slate-200 text-slate-600">
            <tr>
                <th class="px-6 py-3 font-medium">Услуга</th>
                <th class="px-6 py-3 font-medium">Кол-во</th>
                <th class="px-6 py-3 font-medium">Цена за ед.</th>
                <th class="px-6 py-3 font-medium text-right">Сумма</th>
            </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
            @forelse($deal->items as $item)
                <tr>
                    <td class="px-6 py-3.5 font-medium text-slate-900">
                        {{ $item->service?->name ?? 'Услуга #' . $item->service_id }}
                    </td>
                    <td class="px-6 py-3.5 text-slate-700">{{ $item->quantity }}</td>
                    <td class="px-6 py-3.5 text-slate-700">
                        {{ number_format((float) $item->unit_price, 0, ',', ' ') }} ₽
                    </td>
                    <td class="px-6 py-3.5 text-right font-semibold text-slate-900">
                        {{ number_format($item->lineTotal(), 0, ',', ' ') }} ₽
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="4" class="px-6 py-6 text-center text-slate-500">
                        Услуги еще не добавлены.
                    </td>
                </tr>
            @endforelse
            </tbody>
        </table>
    </div>

    {{-- Заглушки под будущие модули Scheduling и Billing --}}
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <div class="bg-white rounded-xl border border-slate-200 p-6">
            <div class="flex items-center justify-between mb-3">
                <h3 class="font-semibold text-slate-900">Визиты по сделке</h3>
                <a href="{{ route('scheduling.index') }}" class="text-xs font-medium text-sky-600 hover:underline">
                    Перейти в расписание →
                </a>
            </div>

            @forelse($appointments as $visit)
                <div class="py-2.5 border-b border-slate-100 last:border-b-0 flex items-start justify-between gap-3 text-sm">
                    <div>
                        <div class="font-medium text-slate-900">
                            {{ $visit->starts_at->format('d.m.Y H:i') }} — {{ $visit->ends_at->format('H:i') }}
                        </div>
                        <div class="text-xs text-slate-500">
                            Врач: {{ $visit->doctor?->name ?? '—' }}
                        </div>
                        @if($visit->notes)
                            <div class="text-xs text-slate-600 mt-1">{{ $visit->notes }}</div>
                        @endif
                    </div>
                    <span class="px-2 py-0.5 rounded-full text-xs font-medium bg-slate-100 text-slate-700 whitespace-nowrap">
                        {{ $visit->status->label() }}
                    </span>
                </div>
            @empty
                <p class="text-sm text-slate-500">Визиты по этой сделке еще не назначены.</p>
            @endforelse
        </div>

        <div class="bg-white rounded-xl border border-slate-200 p-6">
            <h3 class="font-semibold text-slate-900 mb-2">Оплаты и остаток долга</h3>
            <p class="text-sm text-slate-500">Подключится в Модуле 6 (Billing).</p>
        </div>
    </div>

    {{-- Модалка причины отказа --}}
    @if($showLossModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/50 p-4">
            <div class="w-full max-w-md bg-white rounded-2xl shadow-lg border border-slate-200 p-6">
                <h2 class="text-lg font-semibold text-slate-900 mb-2">Укажите причину отказа</h2>
                <form wire:submit="confirmLoss" class="space-y-4">
                    <select wire:model="pendingLossReason"
                            class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                        <option value="">Выберите причину...</option>
                        @foreach($lossReasons as $reason)
                            <option value="{{ $reason->value }}">{{ $reason->label() }}</option>
                        @endforeach
                    </select>
                    @error('pendingLossReason') <p class="text-xs text-rose-600 mt-1">{{ $message }}</p> @enderror

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
