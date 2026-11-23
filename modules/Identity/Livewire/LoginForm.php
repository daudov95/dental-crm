<?php

declare(strict_types=1);

namespace Modules\Identity\Livewire;

use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.guest')]
#[Title('Вход в систему')]
class LoginForm extends Component
{
    public string $email = '';
    public string $password = '';
    public bool $remember = false;

    public function authenticate(): mixed
    {
        $credentials = $this->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        if (! Auth::attempt([...$credentials, 'is_active' => true], $this->remember)) {
            $this->addError('email', 'Неверный email, пароль или учетная запись отключена.');
            return null;
        }

        session()->regenerate();

        return redirect()->intended(route('home'));
    }

    public function render(): View
    {
        return view('identity::livewire.login-form');
    }
}
