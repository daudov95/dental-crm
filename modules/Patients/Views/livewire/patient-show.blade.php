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
        <div class="bg-white rounded-xl border border-slate-200 p-5">
            <h3 class="font-semibold text-slate-900 text-sm">Сделки и планы лечения</h3>
            <p class="text-xs text-slate-500 mt-2">
                История сделок появится после подключения модуля Deals.
            </p>
        </div>

        <div class="bg-white rounded-xl border border-slate-200 p-5">
            <h3 class="font-semibold text-slate-900 text-sm">Визиты и расписание</h3>
            <p class="text-xs text-slate-500 mt-2">
                История приемов появится после подключения модуля Scheduling.
            </p>
        </div>

        <div class="bg-white rounded-xl border border-slate-200 p-5">
            <h3 class="font-semibold text-slate-900 text-sm">Оплаты и рассрочки</h3>
            <p class="text-xs text-slate-500 mt-2">
                История платежей появится после подключения модуля Billing.
            </p>
        </div>
    </div>
</div>
