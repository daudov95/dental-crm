<div class="space-y-6">
    @if (session('status'))
        <div class="rounded-lg bg-emerald-50 border border-emerald-200 px-4 py-3 text-sm text-emerald-800">
            {{ session('status') }}
        </div>
    @endif

    <div class="flex flex-wrap items-center justify-between gap-4">
        <div class="flex flex-wrap items-center gap-3">
            <input type="text" wire:model.live.debounce.300ms="search"
                   placeholder="Поиск по ФИО или телефону..."
                   class="w-72 rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-sky-500">

            <select wire:model.live="sourceFilter"
                    class="rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-sky-500">
                <option value="">Все каналы</option>
                @foreach($sources as $src)
                    <option value="{{ $src->value }}">{{ $src->label() }}</option>
                @endforeach
            </select>

            <select wire:model.live="statusFilter"
                    class="rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-sky-500">
                <option value="active">Активные</option>
                <option value="archived">В архиве</option>
                <option value="all">Все записи</option>
            </select>
        </div>

        @if($canManage)
            <button wire:click="openCreateModal"
                    class="bg-sky-600 hover:bg-sky-700 text-white text-sm font-medium px-4 py-2 rounded-lg transition">
                + Новый пациент
            </button>
        @endif
    </div>

    <div class="bg-white rounded-xl border border-slate-200 overflow-hidden">
        <table class="w-full text-sm text-left">
            <thead class="bg-slate-50 border-b border-slate-200 text-slate-600">
            <tr>
                <th class="px-5 py-3 font-medium">Пациент</th>
                <th class="px-5 py-3 font-medium">Телефон</th>
                <th class="px-5 py-3 font-medium">Канал обращения</th>
                <th class="px-5 py-3 font-medium">Согласие 152-ФЗ</th>
                <th class="px-5 py-3 font-medium text-right">Действия</th>
            </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
            @forelse($patients as $patient)
                <tr class="hover:bg-slate-50/70 {{ $patient->trashed() ? 'opacity-60' : '' }}">
                    <td class="px-5 py-3.5">
                        <a href="{{ route('patients.show', $patient->id) }}"
                           class="font-medium text-slate-900 hover:text-sky-600">
                            {{ $patient->full_name }}
                        </a>
                        @if($patient->birth_date)
                            <div class="text-xs text-slate-500">
                                д.р. {{ $patient->birth_date->format('d.m.Y') }}
                            </div>
                        @endif
                    </td>
                    <td class="px-5 py-3.5 text-slate-700">{{ $patient->phone }}</td>
                    <td class="px-5 py-3.5">
                            <span class="inline-flex px-2.5 py-0.5 rounded-full text-xs font-medium bg-sky-50 text-sky-700">
                                {{ $patient->source->label() }}
                            </span>
                    </td>
                    <td class="px-5 py-3.5">
                        @if($patient->consent_given)
                            <span class="inline-flex px-2.5 py-0.5 rounded-full text-xs font-medium bg-emerald-50 text-emerald-700">Подписано</span>
                        @else
                            <span class="inline-flex px-2.5 py-0.5 rounded-full text-xs font-medium bg-amber-50 text-amber-700">Не оформлено</span>
                        @endif
                    </td>
                    <td class="px-5 py-3.5 text-right space-x-2">
                        <a href="{{ route('patients.show', $patient->id) }}"
                           class="text-slate-600 hover:text-slate-900 font-medium">
                            Карточка
                        </a>
                        @if($canManage)
                            <button wire:click="openEditModal({{ $patient->id }})"
                                    class="text-sky-600 hover:text-sky-800 font-medium">
                                Изменить
                            </button>
                            @if($patient->trashed())
                                <button wire:click="restore({{ $patient->id }})"
                                        class="text-emerald-600 hover:text-emerald-800 font-medium">
                                    Восстановить
                                </button>
                            @else
                                <button wire:click="archive({{ $patient->id }})"
                                        class="text-slate-500 hover:text-rose-600 font-medium">
                                    В архив
                                </button>
                            @endif
                        @endif
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="5" class="px-5 py-8 text-center text-slate-500">
                        Пациенты не найдены.
                    </td>
                </tr>
            @endforelse
            </tbody>
        </table>
    </div>

    <div>
        {{ $patients->links() }}
    </div>

    @if($showModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/50 p-4">
            <div class="w-full max-w-lg bg-white rounded-2xl shadow-lg border border-slate-200 p-6">
                <h2 class="text-lg font-semibold text-slate-900 mb-4">
                    {{ $editingId ? 'Редактирование карточки пациента' : 'Новый пациент' }}
                </h2>

                <form wire:submit="save" class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">ФИО</label>
                        <input type="text" wire:model="fullName" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                        @error('fullName') <p class="text-xs text-rose-600 mt-1">{{ $message }}</p> @enderror
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1">Телефон</label>
                            <input type="text" wire:model="phone" placeholder="+7 (928) 000-00-00"
                                   class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                            @error('phone') <p class="text-xs text-rose-600 mt-1">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1">Дата рождения</label>
                            <input type="date" wire:model="birthDate"
                                   class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                            @error('birthDate') <p class="text-xs text-rose-600 mt-1">{{ $message }}</p> @enderror
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Канал обращения</label>
                        <select wire:model="source" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                            @foreach($sources as $src)
                                <option value="{{ $src->value }}">{{ $src->label() }}</option>
                            @endforeach
                        </select>
                        @error('source') <p class="text-xs text-rose-600 mt-1">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Заметки администратора</label>
                        <textarea rows="3" wire:model="notes"
                                  class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm"
                                  placeholder="Пожелания по времени, интересующие услуги..."></textarea>
                        @error('notes') <p class="text-xs text-rose-600 mt-1">{{ $message }}</p> @enderror
                    </div>

                    <label class="flex items-center gap-2 text-sm text-slate-700">
                        <input type="checkbox" wire:model="consentGiven" class="rounded border-slate-300">
                        Получено согласие на обработку персональных данных (152-ФЗ)
                    </label>

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
</div>
