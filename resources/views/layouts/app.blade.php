<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? 'Панель управления' }} — {{ config('app.name') }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    @livewireStyles
</head>
<body class="bg-slate-50 text-slate-800 antialiased">
<div class="min-h-screen flex">
    <aside class="w-64 bg-slate-900 text-slate-200 flex flex-col shrink-0">
        <div class="px-6 py-5 border-b border-slate-800">
            <div class="text-lg font-bold tracking-tight text-white">{{ config('app.name') }}</div>
            <div class="text-xs text-slate-400 mt-0.5">{{ config('app.clinic_name') }}</div>
        </div>

        <nav class="flex-1 px-3 py-4 space-y-1 text-sm">
            <a href="{{ route('home') }}"
               class="flex items-center px-3 py-2 rounded-lg {{ request()->routeIs('home') ? 'bg-sky-600 text-white' : 'hover:bg-slate-800' }}">
                Главная
            </a>
            @can('viewAny', \Modules\Identity\Models\User::class)
                <a href="{{ route('identity.staff.index') }}"
                   class="flex items-center px-3 py-2 rounded-lg {{ request()->routeIs('identity.staff.*') ? 'bg-sky-600 text-white' : 'hover:bg-slate-800' }}">
                    Сотрудники
                </a>
            @endcan
            <a href="{{ route('catalog.services.index') }}"
               class="flex items-center px-3 py-2 rounded-lg {{ request()->routeIs('catalog.*') ? 'bg-sky-600 text-white' : 'hover:bg-slate-800' }}">
                Справочник услуг
            </a>
            <a href="{{ route('patients.index') }}"
               class="flex items-center px-3 py-2 rounded-lg {{ request()->routeIs('patients.*') ? 'bg-sky-600 text-white' : 'hover:bg-slate-800' }}">
                Пациенты
            </a>
        </nav>

        <div class="p-4 border-t border-slate-800 text-xs">
            <div class="font-medium text-white truncate">{{ auth()->user()->name }}</div>
            <div class="text-slate-400">{{ auth()->user()->role->label() }}</div>
            <form method="POST" action="{{ route('logout') }}" class="mt-3">
                @csrf
                <button type="submit" class="w-full text-left px-3 py-1.5 rounded bg-slate-800 hover:bg-rose-600 text-slate-200 hover:text-white transition">
                    Выйти
                </button>
            </form>
        </div>
    </aside>

    <main class="flex-1 flex flex-col min-w-0">
        <header class="bg-white border-b border-slate-200 px-8 py-4 flex items-center justify-between">
            <h1 class="text-xl font-semibold text-slate-900">{{ $title ?? 'Рабочий стол' }}</h1>
            <span class="text-xs px-2.5 py-1 rounded-full bg-sky-50 text-sky-700 font-medium">
                {{ auth()->user()->role->label() }}
            </span>
        </header>

        <div class="p-8 flex-1">
            {{ $slot ?? '' }}
            @yield('content')
        </div>
    </main>
</div>
@livewireScripts
</body>
</html>
