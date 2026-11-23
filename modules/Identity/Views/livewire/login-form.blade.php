<div class="w-full max-w-md bg-white rounded-2xl shadow-sm border border-slate-200 p-8">
    <div class="mb-6 text-center">
        <h1 class="text-2xl font-bold text-slate-900">{{ config('app.name') }}</h1>
        <p class="text-sm text-slate-500 mt-1">{{ config('app.clinic_name') }} — вход для сотрудников</p>
    </div>

    <form wire:submit="authenticate" class="space-y-4">
        <div>
            <label class="block text-sm font-medium text-slate-700 mb-1">Email</label>
            <input type="email" wire:model="email" required autofocus
                   class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-sky-500"
                   placeholder="director@dentalcrm.local">
            @error('email') <p class="text-xs text-rose-600 mt-1">{{ $message }}</p> @enderror
        </div>

        <div>
            <label class="block text-sm font-medium text-slate-700 mb-1">Пароль</label>
            <input type="password" wire:model="password" required
                   class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-sky-500">
            @error('password') <p class="text-xs text-rose-600 mt-1">{{ $message }}</p> @enderror
        </div>

        <label class="flex items-center gap-2 text-sm text-slate-600">
            <input type="checkbox" wire:model="remember" class="rounded border-slate-300">
            Запомнить меня
        </label>

        <button type="submit"
                class="w-full bg-sky-600 hover:bg-sky-700 text-white font-medium py-2.5 rounded-lg text-sm transition">
            Войти
        </button>
    </form>
</div>
