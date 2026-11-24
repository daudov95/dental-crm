<div class="space-y-6">
    @if (session('status'))
        <div class="rounded-lg bg-emerald-50 border border-emerald-200 px-4 py-3 text-sm text-emerald-800">
            {{ session('status') }}
        </div>
    @endif

    {{-- Сводные финансовые карточки --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="bg-white rounded-xl border border-slate-200 p-4">
            <div class="text-xs font-medium text-slate-500">Поступления за месяц</div>
            <div class="text-2xl font-bold text-slate-900 mt-1">
                {{ number_format($monthRevenue, 0, ',', ' ') }} ₽
            </div>
            <div class="text-xs text-slate-400 mt-1">
                Всего: {{ number_format($totalRevenue, 0, ',', ' ') }} ₽
            </div>
        </div>

        <div class="bg-white rounded-xl border border-slate-200 p-4">
            <div class="text-xs font-medium text-slate-500">Дебиторка (остатки рассрочек)</div>
            <div class="text-2xl font-bold text-amber-600 mt-1">
                {{ number_format($totalReceivables, 0, ',', ' ') }} ₽
            </div>
            <div class="text-xs text-slate-400 mt-1">
                Активных рассрочек: {{ $installmentDeals->count() }}
            </div>
        </div>

        <div class="bg-white rounded-xl border border-slate-200 p-4">
            <div class="text-xs font-medium text-slate-500">Просроченные рассрочки</div>
            <div class="text-2xl font-bold {{ $overdueCount > 0 ? 'text-rose-600' : 'text-emerald-600' }} mt-1">
                {{ $overdueCount }}
            </div>
            <div class="text-xs text-slate-400 mt-1">
                {{ $overdueCount > 0 ? 'Требуется связь с пациентами' : 'Просрочек нет' }}
            </div>
        </div>

        <div class="bg-white rounded-xl border border-slate-200 p-4">
            <div class="text-xs font-medium text-slate-500">Системные уведомления</div>
            <div class="text-2xl font-bold {{ $unreadAlertsCount > 0 ? 'text-sky-600' : 'text-slate-900' }} mt-1">
                {{ $unreadAlertsCount }}
            </div>
            <div class="text-xs text-slate-400 mt-1">
                Непрочитанных алертов
            </div>
        </div>
    </div>

    {{-- Вкладки и кнопки действий --}}
    <div class="flex flex-wrap items-center justify-between gap-4">
        <div class="inline-flex rounded-lg border border-slate-300 bg-white p-0.5 text-sm">
            <button type="button" wire:click="setTab('payments')"
                    class="px-3.5 py-1.5 rounded-md font-medium transition {{ $activeTab === 'payments' ? 'bg-sky-600 text-white' : 'text-slate-600 hover:text-slate-900' }}">
                Журнал платежей
            </button>
            <button type="button" wire:click="setTab('installments')"
                    class="px-3.5 py-1.5 rounded-md font-medium transition flex items-center gap-1.5 {{ $activeTab === 'installments' ? 'bg-sky-600 text-white' : 'text-slate-600 hover:text-slate-900' }}">
                Рассрочки и долги
                @if($overdueCount > 0)
                    <span class="px-1.5 py-0.5 rounded-full text-[11px] {{ $activeTab === 'installments' ? 'bg-white/25 text-white' : 'bg-rose-100 text-rose-700' }}">
                        {{ $overdueCount }}
                    </span>
                @endif
            </button>
            <button type="button" wire:click="setTab('alerts')"
                    class="px-3.5 py-1.5 rounded-md font-medium transition flex items-center gap-1.5 {{ $activeTab === 'alerts' ? 'bg-sky-600 text-white' : 'text-slate-600 hover:text-slate-900' }}">
                Уведомления
                @if($unreadAlertsCount > 0)
                    <span class="px-1.5 py-0.5 rounded-full text-[11px] {{ $activeTab === 'alerts' ? 'bg-white/25 text-white' : 'bg-amber-100 text-amber-800' }}">
                        {{ $unreadAlertsCount }}
                    </span>
                @endif
            </button>
        </div>

        <div class="flex flex-wrap items-center gap-3">
            @if($activeTab === 'alerts')
                <button type="button" wire:click="runBackgroundChecks"
                        class="px-3.5 py-2 text-sm rounded-lg border border-slate-300 bg-white text-slate-700 hover:bg-slate-50 font-medium">
                    Проверить просрочки (Redis)
                </button>
                @if($unreadAlertsCount > 0)
                    <button type="button" wire:click="markAllAlertsRead"
                            class="px-3.5 py-2 text-sm rounded-lg border border-slate-300 bg-white text-slate-700 hover:bg-slate-50 font-medium">
                        Прочитать все
                    </button>
                @endif
            @endif

            <button type="button" wire:click="openPaymentModal"
                    class="bg-sky-600 hover:bg-sky-700 text-white text-sm font-medium px-4 py-2 rounded-lg transition">
                + Принять оплату
            </button>
        </div>
    </div>

    {{-- ВКЛАДКА 1: Журнал платежей --}}
    @if($activeTab === 'payments')
        <div class="flex flex-wrap items-center gap-3">
            <input type="text" wire:model.live.debounce.300ms="search"
                   placeholder="Поиск по пациенту, сделке, комментарию..."
                   class="w-80 rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-sky-500">

            <select wire:model.live="methodFilter"
                    class="rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm">
                <option value="">Все способы оплаты</option>
                @foreach($methods as $m)
                    <option value="{{ $m->value }}">{{ $m->label() }}</option>
                @endforeach
            </select>
        </div>

        <div class="bg-white rounded-xl border border-slate-200 overflow-hidden">
            <table class="w-full text-sm text-left">
                <thead class="bg-slate-50 border-b border-slate-200 text-slate-600">
                <tr>
                    <th class="px-5 py-3 font-medium">Дата</th>
                    <th class="px-5 py-3 font-medium">Пациент / Сделка</th>
                    <th class="px-5 py-3 font-medium">Способ</th>
                    <th class="px-5 py-3 font-medium">Принял</th>
                    <th class="px-5 py-3 font-medium">Комментарий</th>
                    <th class="px-5 py-3 font-medium text-right">Сумма</th>
                </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                @forelse($payments as $payment)
                    <tr wire:key="payment-row-{{ $payment->id }}" class="hover:bg-slate-50/70">
                        <td class="px-5 py-3.5 whitespace-nowrap text-slate-700">
                            {{ $payment->paid_at->format('d.m.Y H:i') }}
                        </td>
                        <td class="px-5 py-3.5">
                            @if($payment->deal)
                                <a href="{{ route('deals.show', $payment->deal_id) }}"
                                   class="font-medium text-slate-900 hover:text-sky-600">
                                    {{ $payment->deal->title }}
                                </a>
                                <div class="text-xs text-slate-500 mt-0.5">
                                    {{ $payment->deal->patient?->full_name ?? 'Пациент удален' }}
                                </div>
                            @else
                                <span class="text-slate-400">Сделка удалена</span>
                            @endif
                        </td>
                        <td class="px-5 py-3.5">
                                <span class="inline-flex px-2.5 py-0.5 rounded-full text-xs font-medium bg-slate-100 text-slate-700">
                                    {{ $payment->method->label() }}
                                </span>
                        </td>
                        <td class="px-5 py-3.5 text-slate-600">
                            {{ $payment->receiver?->name ?? '—' }}
                        </td>
                        <td class="px-5 py-3.5 text-xs text-slate-600 max-w-xs">
                            {{ $payment->comment ?? '—' }}
                        </td>
                        <td class="px-5 py-3.5 text-right font-semibold text-emerald-700 whitespace-nowrap">
                            + {{ number_format((float) $payment->amount, 0, ',', ' ') }} ₽
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-5 py-8 text-center text-slate-500">
                            Платежи не найдены.
                        </td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>
    @endif

    {{-- ВКЛАДКА 2: Активные рассрочки и остатки долга --}}
    @if($activeTab === 'installments')
        <div class="bg-white rounded-xl border border-slate-200 overflow-hidden">
            <table class="w-full text-sm text-left">
                <thead class="bg-slate-50 border-b border-slate-200 text-slate-600">
                <tr>
                    <th class="px-5 py-3 font-medium">Пациент / Сделка</th>
                    <th class="px-5 py-3 font-medium">Сумма сделки</th>
                    <th class="px-5 py-3 font-medium">Оплачено</th>
                    <th class="px-5 py-3 font-medium">Остаток долга</th>
                    <th class="px-5 py-3 font-medium">След. платеж</th>
                    <th class="px-5 py-3 font-medium text-right">Действие</th>
                </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                @forelse($installmentDeals as $row)
                    @php($deal = $row['deal'])
                    <tr wire:key="installment-row-{{ $deal->id }}" class="hover:bg-slate-50/70 {{ $deal->is_overdue ? 'bg-rose-50/40' : '' }}">
                        <td class="px-5 py-3.5">
                            <a href="{{ route('deals.show', $deal->id) }}" class="font-medium text-slate-900 hover:text-sky-600">
                                {{ $deal->title }}
                            </a>
                            <div class="text-xs text-slate-500 mt-0.5">
                                {{ $deal->patient?->full_name }}
                                @if($deal->patient?->phone)
                                    • {{ $deal->patient->phone }}
                                @endif
                            </div>
                        </td>
                        <td class="px-5 py-3.5 text-slate-700 whitespace-nowrap">
                            {{ number_format($row['total'], 0, ',', ' ') }} ₽
                        </td>
                        <td class="px-5 py-3.5 text-emerald-700 font-medium whitespace-nowrap">
                            {{ number_format($row['paid'], 0, ',', ' ') }} ₽
                        </td>
                        <td class="px-5 py-3.5 font-semibold whitespace-nowrap {{ $row['remaining'] > 0 ? 'text-slate-900' : 'text-emerald-600' }}">
                            {{ number_format($row['remaining'], 0, ',', ' ') }} ₽
                        </td>
                        <td class="px-5 py-3.5 whitespace-nowrap">
                            @if($deal->next_payment_date)
                                <span class="inline-flex px-2.5 py-0.5 rounded-full text-xs font-medium {{ $deal->is_overdue ? 'bg-rose-100 text-rose-700' : 'bg-amber-50 text-amber-700' }}">
                                        {{ $deal->next_payment_date->format('d.m.Y') }}
                                    {{ $deal->is_overdue ? '(Просрочка)' : '' }}
                                    </span>
                            @else
                                <span class="text-xs text-slate-400">Не задана</span>
                            @endif
                        </td>
                        <td class="px-5 py-3.5 text-right whitespace-nowrap">
                            @if($row['remaining'] > 0)
                                <button type="button" wire:click="openPaymentModal({{ $deal->id }})"
                                        class="text-sky-600 hover:text-sky-800 font-medium">
                                    Внести платеж
                                </button>
                            @else
                                <span class="text-xs text-emerald-600 font-medium">Погашено</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-5 py-8 text-center text-slate-500">
                            Активных сделок в рассрочку нет.
                        </td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>
    @endif

    {{-- ВКЛАДКА 3: Системные уведомления (алерты) --}}
    @if($activeTab === 'alerts')
        <div class="space-y-3">
            @forelse($alerts as $alert)
                <div wire:key="alert-card-{{ $alert->id }}"
                     class="bg-white rounded-xl border p-4 flex items-start justify-between gap-4 {{ $alert->is_read ? 'border-slate-200 opacity-70' : ($alert->type === 'overdue_installment' ? 'border-rose-300 bg-rose-50/30' : 'border-amber-300 bg-amber-50/30') }}">
                    <div class="space-y-1">
                        <div class="flex items-center gap-2">
                            <span class="px-2 py-0.5 rounded-full text-[11px] font-medium {{ $alert->type === 'overdue_installment' ? 'bg-rose-100 text-rose-700' : 'bg-amber-100 text-amber-800' }}">
                                {{ $alert->type === 'overdue_installment' ? 'Просрочка рассрочки' : 'Напоминания' }}
                            </span>
                            <span class="text-xs text-slate-400">{{ $alert->created_at?->format('d.m.Y H:i') }}</span>
                        </div>
                        <div class="font-semibold text-sm text-slate-900">{{ $alert->title }}</div>
                        <p class="text-sm text-slate-600">{{ $alert->message }}</p>

                        <div class="pt-1 flex items-center gap-3 text-xs">
                            @if($alert->type === 'overdue_installment' && $alert->entity_id)
                                <a href="{{ route('deals.show', $alert->entity_id) }}" class="text-sky-600 hover:underline font-medium">
                                    Открыть сделку →
                                </a>
                                <button type="button" wire:click="openPaymentModal({{ $alert->entity_id }})"
                                        class="text-emerald-700 hover:underline font-medium">
                                    Принять оплату
                                </button>
                            @elseif($alert->type === 'tomorrow_reminders')
                                <a href="{{ route('scheduling.index') }}" class="text-sky-600 hover:underline font-medium">
                                    Открыть расписание →
                                </a>
                            @endif
                        </div>
                    </div>

                    @if(! $alert->is_read)
                        <button type="button" wire:click="markAlertRead({{ $alert->id }})"
                                class="text-xs font-medium text-slate-500 hover:text-slate-900 whitespace-nowrap">
                            Отметить прочитанным
                        </button>
                    @else
                        <span class="text-xs text-slate-400 whitespace-nowrap">Прочитано</span>
                    @endif
                </div>
            @empty
                <div class="bg-white rounded-xl border border-slate-200 p-8 text-center text-sm text-slate-500">
                    Системных уведомлений пока нет.
                </div>
            @endforelse
        </div>
    @endif

    {{-- Модалка приема платежа --}}
    @if($showModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/50 p-4 overflow-y-auto">
            <div class="w-full max-w-lg bg-white rounded-2xl shadow-lg border border-slate-200 p-6 my-8">
                <h2 class="text-lg font-semibold text-slate-900 mb-4">Прием оплаты по сделке</h2>

                <form wire:submit="recordPayment" class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Сделка</label>
                        <select wire:model.live="dealId" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                            <option value="">Выберите сделку...</option>
                            @foreach($availableDeals as $d)
                                <option value="{{ $d->id }}">
                                    {{ $d->title }} — {{ $d->patient?->full_name }} ({{ number_format((float) $d->total_amount, 0, ',', ' ') }} ₽)
                                </option>
                            @endforeach
                        </select>
                        @error('dealId') <p class="text-xs text-rose-600 mt-1">{{ $message }}</p> @enderror
                    </div>

                    @if($selectedDealSummary)
                        <div class="rounded-xl bg-slate-50 border border-slate-200 p-3 text-xs grid grid-cols-3 gap-2">
                            <div>
                                <div class="text-slate-500">Сумма сделки</div>
                                <div class="font-semibold text-slate-900 text-sm">
                                    {{ number_format($selectedDealSummary['total'], 0, ',', ' ') }} ₽
                                </div>
                            </div>
                            <div>
                                <div class="text-slate-500">Уже оплачено</div>
                                <div class="font-semibold text-emerald-700 text-sm">
                                    {{ number_format($selectedDealSummary['paid'], 0, ',', ' ') }} ₽
                                </div>
                            </div>
                            <div>
                                <div class="text-slate-500">Остаток</div>
                                <div class="font-semibold text-amber-700 text-sm">
                                    {{ number_format($selectedDealSummary['remaining'], 0, ',', ' ') }} ₽
                                </div>
                            </div>
                        </div>
                    @endif

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1">Сумма платежа (₽)</label>
                            <input type="number" step="0.01" min="1" wire:model="amount"
                                   class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                            @error('amount') <p class="text-xs text-rose-600 mt-1">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1">Способ оплаты</label>
                            <select wire:model="method" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                                @foreach($methods as $m)
                                    <option value="{{ $m->value }}">{{ $m->label() }}</option>
                                @endforeach
                            </select>
                            @error('method') <p class="text-xs text-rose-600 mt-1">{{ $message }}</p> @enderror
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1">Дата и время оплаты</label>
                            <input type="datetime-local" wire:model="paidAt"
                                   class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                            @error('paidAt') <p class="text-xs text-rose-600 mt-1">{{ $message }}</p> @enderror
                        </div>

                        @if($selectedDealSummary && $selectedDealSummary['deal']->is_installment)
                            <div>
                                <label class="block text-sm font-medium text-slate-700 mb-1">Новая дата след. платежа</label>
                                <input type="date" wire:model="nextPaymentDate"
                                       class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                                @error('nextPaymentDate') <p class="text-xs text-rose-600 mt-1">{{ $message }}</p> @enderror
                            </div>
                        @endif
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Комментарий</label>
                        <textarea rows="2" wire:model="comment" placeholder="Например: взнос за 2-й месяц рассрочки"
                                  class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm"></textarea>
                        @error('comment') <p class="text-xs text-rose-600 mt-1">{{ $message }}</p> @enderror
                    </div>

                    <div class="flex justify-end gap-3 pt-2">
                        <button type="button" wire:click="$set('showModal', false)"
                                class="px-4 py-2 text-sm rounded-lg border border-slate-300 text-slate-700 hover:bg-slate-50">
                            Отмена
                        </button>
                        <button type="submit"
                                class="px-4 py-2 text-sm rounded-lg bg-sky-600 hover:bg-sky-700 text-white font-medium">
                            Зафиксировать оплату
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif
</div>
