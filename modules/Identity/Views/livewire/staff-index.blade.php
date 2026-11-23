<div class="space-y-6">
    @if (session('status'))
        <div class="rounded-lg bg-emerald-50 border border-emerald-200 px-4 py-3 text-sm text-emerald-800">
            {{ session('status') }}
        </div>
    @endif

    <div class="flex flex-wrap items-center justify-between gap-4">
        <div class="flex flex-wrap items-center gap-3">
            <input type="text" wire:model.live.debounce.300ms="search"
                   placeholder="Поиск по ФИО, email, специализации..."
                   class="w-72 rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-sky-500">

            <select wire:model.live="roleFilter"
                    class="rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-sky-500">
                <option value="">Все роли</option>
                @foreach($roles as $roleItem)
                    <option value="{{ $roleItem->value }}">{{ $roleItem->label() }}</option>
                @endforeach
            </select>
        </div>

        <button wire:click="openCreateModal"
                class="bg-sky-600 hover:bg-sky-700 text-white text-sm font-medium px-4 py-2 rounded-lg transition">
            + Добавить сотрудника
        </button>
    </div>

    <div class="bg-white rounded-xl border border-slate-200 overflow-hidden">
        <table class="w-full text-sm text-left">
            <thead class="bg-slate-50 border-b border-slate-200 text-slate-600">
            <tr>
                <th class="px-5 py-3 font-medium">Сотрудник</th>
                <th class="px-5 py-3 font-medium">Роль</th>
                <th class="px-5 py-3 font-medium">Специализация</th>
                <th class="px-5 py-3 font-medium">Статус</th>
                <th class="px-5 py-3 font-medium text-right">Действия</th>
            </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
            @forelse($staff as $member)
                <tr class="hover:bg-slate-50/70">
                    <td class="px-5 py-3.5">
                        <div class="font-medium text-slate-900">{{ $member->name }}</div>
                        <div class="text-xs text-slate-500">{{ $member->email }}</div>
                    </td>
                    <td class="px-5 py-3.5">
                            <span class="inline-flex px-2.5 py-0.5 rounded-full text-xs font-medium bg-slate-100 text-slate-700">
                                {{ $member->role->label() }}
                            </span>
                    </td>
                    <td class="px-5 py-3.5 text-slate-600">
                        {{ $member->specialization ?? '—' }}
                    </td>
                    <td class="px-5 py-3.5">
                        @if($member->is_active)
                            <span class="inline-flex px-2.5 py-0.5 rounded-full text-xs font-medium bg-emerald-50 text-emerald-700">Активен</span>
                        @else
                            <span class="inline-flex px-2.5 py-0.5 rounded-full text-xs font-medium bg-rose-50 text-rose-700">Отключен</span>
                        @endif
                    </td>
                    <td class="px-5 py-3.5 text-right space-x-2">
                        <button wire:click="openEditModal({{ $member->id }})"
                                class="text-sky-600 hover:text-sky-800 font-medium">
                            Изменить
                        </button>
                        @if($member->id !== auth()->id())
                            <button wire:click="toggleActive({{ $member->id }})"
                                    class="text-slate-500 hover:text-rose-600 font-medium">
                                {{ $member->is_active ? 'Отключить' : 'Включить' }}
                            </button>
                        @endif
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="5" class="px-5 py-8 text-center text-slate-500">Сотрудники не найдены.</td>
                </tr>
            @endforelse
            </tbody>
        </table>
    </div>

    @if($showModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/50 p-4">
            <div class="w-full max-w-lg bg-white rounded-2xl shadow-lg border border-slate-200 p-6">
                <h2 class="text-lg font-semibold text-slate-900 mb-4">
                    {{ $editingId ? 'Редактирование сотрудника' : 'Новый сотрудник' }}
                </h2>

                <form wire:submit="save" class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">ФИО</label>
                        <input type="text" wire:model="name" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                        @error('name') <p class="text-xs text-rose-600 mt-1">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Email</label>
                        <input type="email" wire:model="email" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                        @error('email') <p class="text-xs text-rose-600 mt-1">{{ $message }}</p> @enderror
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1">Роль</label>
                            <select wire:model="role" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                                @foreach($roles as $roleItem)
                                    <option value="{{ $roleItem->value }}">{{ $roleItem->label() }}</option>
                                @endforeach
                            </select>
                            @error('role') <p class="text-xs text-rose-600 mt-1">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1">Специализация</label>
                            <input type="text" wire:model="specialization" placeholder="Напр. Врач-ортодонт"
                                   class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                            @error('specialization') <p class="text-xs text-rose-600 mt-1">{{ $message }}</p> @enderror
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">
                            Пароль {{ $editingId ? '(оставьте пустым, чтобы не менять)' : '' }}
                        </label>
                        <input type="password" wire:model="password" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                        @error('password') <p class="text-xs text-rose-600 mt-1">{{ $message }}</p> @enderror
                    </div>

                    <label class="flex items-center gap-2 text-sm text-slate-700">
                        <input type="checkbox" wire:model="isActive" class="rounded border-slate-300">
                        Учетная запись активна
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
