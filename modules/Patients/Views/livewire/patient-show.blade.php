<div class="space-y-6">
    <div class="flex items-center justify-between">
        <a href="{{ route('patients.index') }}" class="text-sm text-sky-600 hover:text-sky-800 font-medium">
            ← Вернуться к списку пациентов
        </a>
        @if($patient->trashed())
            <span class="px-3 py-1 rounded-full text-xs font-medium bg-rose-50 text-rose-700">
                Карточка в архиве
            </span>
        @endif
    </div>

    <div class="bg-white rounded-xl border border-slate-200 p-6">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div>
                <h2 class="text-xl font-bold text-slate-900">{{ $patient->full_name }}</h2>
                <p class="text-sm text-slate-600 mt-1">{{ $patient->phone }}</p>
            </div>

            <div class="flex items-center gap-2">
                <span class="px-2.5 py-1 rounded-full text-xs font-medium bg-sky-50 text-sky-700">
                    Канал: {{ $patient->source->label() }}
                </span>
                @if($patient->consent_given)
                    <span class="px-2.5 py-1 rounded-full text-xs font-medium bg-emerald-50 text-emerald-700">
                        Согласие 152-ФЗ подписано
                    </span>
                @else
                    <span class="px-2.5 py-1 rounded-full text-xs font-medium bg-amber-50 text-amber-700">
                        Нет согласия 152-ФЗ
                    </span>
                @endif
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mt-6 pt-6 border-t border-slate-100 text-sm">
            <div>
                <div class="text-xs text-slate-500">Дата рождения</div>
                <div class="font-medium text-slate-900 mt-0.5">
                    {{ $patient->birth_date ? $patient->birth_date->format('d.m.Y') : 'Не указана' }}
                </div>
            </div>
            <div>
                <div class="text-xs text-slate-500">Дата первичной регистрации</div>
                <div class="font-medium text-slate-900 mt-0.5">
                    {{ $patient->created_at->format('d.m.Y H:i') }}
                </div>
            </div>
            <div>
                <div class="text-xs text-slate-500">Заметки</div>
                <div class="text-slate-700 mt-0.5">{{ $patient->notes ?: '—' }}</div>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="bg-white rounded-xl border border-slate-200 p-5 flex flex-col">
            <div class="flex items-center justify-between mb-3">
                <h3 class="font-semibold text-slate-900 text-sm">Сделки и планы лечения</h3>
                <span class="text-xs text-slate-500">{{ $deals->count() }}</span>
            </div>

            @forelse($deals as $deal)
                <div class="py-3 border-b border-slate-100 last:border-b-0 space-y-1">
                    <div class="flex items-start justify-between gap-2">
                        <a href="{{ route('deals.show', $deal->id) }}"
                           class="text-sm font-medium text-slate-900 hover:text-sky-600 leading-snug">
                            {{ $deal->title }}
                        </a>
                        <span class="text-xs font-semibold text-slate-900 whitespace-nowrap">
                            {{ number_format((float) $deal->total_amount, 0, ',', ' ') }} ₽
                        </span>
                    </div>
                    <div class="flex flex-wrap items-center gap-2 text-xs text-slate-500">
                        <span class="px-2 py-0.5 rounded-full bg-slate-100 text-slate-700 font-medium">
                            {{ $deal->status->label() }}
                        </span>
                        @if($deal->is_installment)
                            <span class="px-2 py-0.5 rounded-full font-medium {{ $deal->is_overdue ? 'bg-rose-100 text-rose-700' : 'bg-amber-50 text-amber-700' }}">
                                {{ $deal->is_overdue ? 'Просрочка' : 'Рассрочка' }}
                            </span>
                        @endif
                        @if($deal->doctor)
                            <span>{{ $deal->doctor->name }}</span>
                        @endif
                    </div>
                </div>
            @empty
                <p class="text-xs text-slate-500">Сделок по пациенту пока нет.</p>
            @endforelse
        </div>

        <div class="bg-white rounded-xl border border-slate-200 p-5 flex flex-col">
            <div class="flex items-center justify-between mb-3">
                <h3 class="font-semibold text-slate-900 text-sm">Визиты и расписание</h3>
                <span class="text-xs text-slate-500">{{ $appointments->count() }}</span>
            </div>

            @forelse($appointments as $visit)
                <div class="py-3 border-b border-slate-100 last:border-b-0 space-y-1">
                    <div class="flex items-start justify-between gap-2">
                        <div class="text-sm font-medium text-slate-900">
                            {{ $visit->starts_at->format('d.m.Y H:i') }} — {{ $visit->ends_at->format('H:i') }}
                        </div>
                        <span class="px-2 py-0.5 rounded-full text-xs font-medium bg-slate-100 text-slate-700 whitespace-nowrap">
                            {{ $visit->status->label() }}
                        </span>
                    </div>
                    <div class="text-xs text-slate-500">
                        Врач: {{ $visit->doctor?->name ?? '—' }}
                    </div>
                    @if($visit->deal)
                        <div class="text-xs">
                            <a href="{{ route('deals.show', $visit->deal->id) }}" class="text-sky-600 hover:underline">
                                {{ $visit->deal->title }}
                            </a>
                        </div>
                    @endif
                    @if($visit->notes)
                        <div class="text-xs text-slate-600">{{ $visit->notes }}</div>
                    @endif
                </div>
            @empty
                <p class="text-xs text-slate-500">Визитов по пациенту пока нет.</p>
            @endforelse
        </div>

        <div class="bg-white rounded-xl border border-slate-200 p-5 flex flex-col">
            <div class="flex items-center justify-between mb-3">
                <h3 class="font-semibold text-slate-900 text-sm">Оплаты и рассрочки</h3>
                @if($canViewBilling)
                    <span class="text-xs text-slate-500">{{ $payments->count() }}</span>
                @endif
            </div>

            @if($canViewBilling)
                <div class="grid grid-cols-2 gap-2 mb-3 p-3 rounded-lg bg-slate-50 border border-slate-200 text-xs">
                    <div>
                        <div class="text-slate-500">Оплачено</div>
                        <div class="text-sm font-bold text-emerald-700">
                            {{ number_format($paidTotal, 0, ',', ' ') }} ₽
                        </div>
                    </div>
                    <div>
                        <div class="text-slate-500">Остаток</div>
                        <div class="text-sm font-bold {{ $remainingTotal > 0 ? 'text-amber-700' : 'text-emerald-600' }}">
                            {{ number_format($remainingTotal, 0, ',', ' ') }} ₽
                        </div>
                    </div>
                </div>

                @forelse($payments as $pay)
                    <div class="py-2.5 border-b border-slate-100 last:border-b-0 flex items-start justify-between gap-2 text-xs">
                        <div>
                            <div class="font-medium text-slate-900">
                                {{ $pay->paid_at->format('d.m.Y H:i') }} • {{ $pay->method->label() }}
                            </div>
                            @if($pay->deal)
                                <div class="text-slate-500 mt-0.5">{{ $pay->deal->title }}</div>
                            @endif
                            @if($pay->comment)
                                <div class="text-slate-500 mt-0.5">{{ $pay->comment }}</div>
                            @endif
                        </div>
                        <span class="font-semibold text-emerald-700 whitespace-nowrap">
                            + {{ number_format((float) $pay->amount, 0, ',', ' ') }} ₽
                        </span>
                    </div>
                @empty
                    <p class="text-xs text-slate-500">Платежей по пациенту еще не было.</p>
                @endforelse
            @else
                <p class="text-xs text-slate-500">
                    Финансовая информация доступна только администратору и руководителю.
                </p>
            @endif
        </div>
    </div>
</div>
