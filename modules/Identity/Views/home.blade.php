@extends('layouts.app', ['title' => 'Рабочий стол'])

@section('content')
    <div class="bg-white rounded-xl border border-slate-200 p-6">
        <h2 class="text-lg font-semibold text-slate-900">Добро пожаловать, {{ auth()->user()->name }}</h2>
        <p class="text-sm text-slate-600 mt-1">
            Ваша роль: <strong>{{ auth()->user()->role->label() }}</strong>
            @if(auth()->user()->specialization)
                ({{ auth()->user()->specialization }})
            @endif
        </p>
    </div>
@endsection
