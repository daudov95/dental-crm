<div class="space-y-6">
    @if (session('status'))
        <div class="rounded-lg bg-emerald-50 border border-emerald-200 px-4 py-3 text-sm text-emerald-800">
            {{ session('status') }}
        </div>
    @endif

    <div class="flex flex-wrap items-center justify-between gap-4">
        <div class="flex flex-wrap items-center gap-3">
            <input type="text" wire:model.live.debounce.300ms="search"
                   placeholder="Поиск услуги по названию..."
                   class="w-72 rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-sky-500">

            <select wire:model.live="categoryFilter"
                    class="rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-sky-500">
                <option value="">Все направления</option>
                @foreach($categories as $cat)
                    <option value="{{ $cat->value }}">{{ $cat->label() }}</option>
                @endforeach
            </select>

            <select wire:model.live="statusFilter"
                    class="rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-sky-500">
                <option value="active">Только активные</option>
                <option value="all">Все услуги</option>
                <option value="inactive">В архиве</option>
            </select>
        </div>

        @if($canManage)
            <button wire:click="openCreateModal"
                    class="bg-sky-600 hover:bg-sky-700 text-white text-sm font-medium px-4 py-2 rounded-lg transition">
                + Добавить услугу
            </button>
        @endif
    </div>

    <div class="bg-white rounded-xl border border-slate-200 overflow-hidden">
        <table class="w-full text-sm text-left">
            <thead class="bg-slate-50 border-b border-slate-200 text-slate-600">
            <tr>
                <th class="px-5 py-3 font-medium">Наименование услуги</th>
                <th class="px-5 py-3 font-medium">Направление</th>
                <th class="px-5 py-3 font-medium">Длительность</th>
                <th class="px-5 py-3 font-medium">Стоимость</th>
                <th class="px-5 py-3 font-medium">Статус</th>
                @if($canManage)
                    <th class="px-5 py-3 font-medium text-right">Действия</th>
                @endif
            </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
            @forelse($services as $service)
                <tr class="hover:bg-slate-50/70">
                    <td class="px-5 py-3.5 font-medium text-slate-900">{{ $service->name }}</td>
                    <td class="px-5 py-3.5">
                            <span class="inline-flex px-2.5 py-0.5 rounded-full text-xs font-medium bg-sky-50 text-sky-700">
                                {{ $service->category->label() }}
                            </span>
                    </td>
                    <td class="px-5 py-3.5 text-slate-600">{{ $service->duration_minutes }} мин</td>
                    <td class="px-5 py-3.5 font-medium text-slate-900">
                        @if((float) $service->price === 0.0)
                            <span class="text-emerald-700">Бесплатно</span>
                        @else
                            {{ number_format((float) $service->price, 0, ',', ' ') }} ₽
                        @endif
                    </td>
                    <td class="px-5 py-3.5">
                        @if($service->is_active)
                            <span class="inline-flex px-2.5 py-0.5 rounded-full text-xs font-medium bg-emerald-50 text-emerald-700">Активна</span>
                        @else
                            <span class="inline-flex px-2.5 py-0.5 rounded-full text-xs font-medium bg-slate-100 text-slate-600">Архив</span>
                        @endif
                    </td>
                    @if($canManage)
                        <td class="px-5 py-3.5 text-right space-x-2">
                            <button wire:click="openEditModal({{ $service->id }})"
                                    class="text-sky-600 hover:text-sky-800 font-medium">
                                Изменить
                            </button>
                            <button wire:click="toggleActive({{ $service->id }})"
                                    class="text-slate-500 hover:text-rose-600 font-medium">
                                {{ $service->is_active ? 'В архив' : 'Восстановить' }}
                            </button>
                        </td>
                    @endif
                </tr>
            @empty
                <tr>
                    <td colspan="{{ $canManage ? 6 : 5 }}" class="px-5 py-8 text-center text-slate-500">
                        Услуги не найдены.
                    </td>
                </tr>
            @endforelse
            </tbody>
        </table>
    </div>

    @if($showModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/50 p-4">
            <div class="w-full max-w-lg bg-white rounded-2xl shadow-lg border border-slate-200 p-6">
                <h2 class="text-lg font-semibold text-slate-900 mb-4">
                    {{ $editingId ? 'Редактирование услуги' : 'Новая услуга' }}
                </h2>

                <form wire:submit="save" class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Наименование</label>
                        <input type="text" wire:model="name" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                        @error('name') <p class="text-xs text-rose-600 mt-1">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Направление</label>
                        <select wire:model="category" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                            @foreach($categories as $cat)
                                <option value="{{ $cat->value }}">{{ $cat->label() }}</option>
                            @endforeach
                        </select>
                        @error('category') <p class="text-xs text-rose-600 mt-1">{{ $message }}</p> @enderror
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1">Стоимость (₽)</label>
                            <input type="number" step="0.01" min="0" wire:model="price"
                                   class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                            @error('price') <p class="text-xs text-rose-600 mt-1">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1">Длительность (мин)</label>
                            <input type="number" step="5" min="10" max="480" wire:model="durationMinutes"
                                   class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                            @error('durationMinutes') <p class="text-xs text-rose-600 mt-1">{{ $message }}</p> @enderror
                        </div>
                    </div>

                    <label class="flex items-center gap-2 text-sm text-slate-700">
                        <input type="checkbox" wire:model="isActive" class="rounded border-slate-300">
                        Доступна в прайсе
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
