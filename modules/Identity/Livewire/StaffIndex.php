<?php

declare(strict_types=1);

namespace Modules\Identity\Livewire;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Modules\Identity\Actions\CreateStaffMemberAction;
use Modules\Identity\Actions\ToggleStaffActiveAction;
use Modules\Identity\Actions\UpdateStaffMemberAction;
use Modules\Identity\DTO\StaffMemberDTO;
use Modules\Identity\Enums\UserRole;
use Modules\Identity\Models\User;

#[Layout('layouts.app')]
#[Title('Сотрудники')]
class StaffIndex extends Component
{
    use AuthorizesRequests;

    public string $search = '';
    public string $roleFilter = '';

    public bool $showModal = false;
    public ?int $editingId = null;

    public string $name = '';
    public string $email = '';
    public string $role = 'admin';
    public string $specialization = '';
    public string $password = '';
    public bool $isActive = true;

    public function mount(): void
    {
        $this->authorize('viewAny', User::class);
    }

    public function openCreateModal(): void
    {
        $this->authorize('create', User::class);
        $this->resetForm();
        $this->showModal = true;
    }

    public function openEditModal(int $userId): void
    {
        $user = User::findOrFail($userId);
        $this->authorize('update', $user);

        $this->editingId = $user->id;
        $this->name = $user->name;
        $this->email = $user->email;
        $this->role = $user->role->value;
        $this->specialization = $user->specialization ?? '';
        $this->password = '';
        $this->isActive = $user->is_active;
        $this->resetValidation();
        $this->showModal = true;
    }

    public function save(
        CreateStaffMemberAction $createAction,
        UpdateStaffMemberAction $updateAction,
    ): void {
        $isEditing = $this->editingId !== null;

        $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($this->editingId),
            ],
            'role' => ['required', Rule::enum(UserRole::class)],
            'specialization' => ['nullable', 'string', 'max:120'],
            'password' => [$isEditing ? 'nullable' : 'required', 'string', 'min:6'],
            'isActive' => ['boolean'],
        ]);

        $dto = new StaffMemberDTO(
            name: $this->name,
            email: $this->email,
            role: UserRole::from($this->role),
            specialization: $this->specialization !== '' ? $this->specialization : null,
            password: $this->password !== '' ? $this->password : null,
            isActive: $this->isActive,
        );

        if ($isEditing) {
            $user = User::findOrFail($this->editingId);
            $this->authorize('update', $user);
            $updateAction->execute($user, $dto);
            session()->flash('status', 'Данные сотрудника обновлены.');
        } else {
            $this->authorize('create', User::class);
            $createAction->execute($dto);
            session()->flash('status', 'Сотрудник добавлен.');
        }

        $this->showModal = false;
        $this->resetForm();
    }

    public function toggleActive(int $userId, ToggleStaffActiveAction $action): void
    {
        $user = User::findOrFail($userId);
        $this->authorize('toggleActive', $user);

        $action->execute($user);
        session()->flash('status', $user->is_active ? 'Учетная запись активирована.' : 'Учетная запись отключена.');
    }

    private function resetForm(): void
    {
        $this->editingId = null;
        $this->name = '';
        $this->email = '';
        $this->role = UserRole::Admin->value;
        $this->specialization = '';
        $this->password = '';
        $this->isActive = true;
        $this->resetValidation();
    }

    public function render(): View
    {
        $staff = User::query()
            ->when($this->search !== '', function ($query): void {
                $term = '%' . mb_strtolower($this->search) . '%';
                $query->where(function ($q) use ($term): void {
                    $q->whereRaw('LOWER(name) LIKE ?', [$term])
                        ->orWhereRaw('LOWER(email) LIKE ?', [$term])
                        ->orWhereRaw('LOWER(COALESCE(specialization, \'\')) LIKE ?', [$term]);
                });
            })
            ->when($this->roleFilter !== '', fn ($q) => $q->where('role', $this->roleFilter))
            ->orderByDesc('is_active')
            ->orderBy('name')
            ->get();

        return view('identity::livewire.staff-index', [
            'staff' => $staff,
            'roles' => UserRole::cases(),
        ]);
    }
}
