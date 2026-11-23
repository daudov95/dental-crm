<?php

declare(strict_types=1);

namespace Modules\Catalog\Livewire;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Modules\Catalog\Actions\CreateServiceAction;
use Modules\Catalog\Actions\ToggleServiceActiveAction;
use Modules\Catalog\Actions\UpdateServiceAction;
use Modules\Catalog\DTO\ServiceDTO;
use Modules\Catalog\Enums\ServiceCategory;
use Modules\Catalog\Models\Service;

#[Layout('layouts.app')]
#[Title('Справочник услуг')]
class ServiceIndex extends Component
{
    use AuthorizesRequests;

    public string $search = '';
    public string $categoryFilter = '';
    public string $statusFilter = 'active';

    public bool $showModal = false;
    public ?int $editingId = null;

    public string $name = '';
    public string $category = 'therapy';
    public string $price = '0';
    public int $durationMinutes = 30;
    public bool $isActive = true;

    public function mount(): void
    {
        $this->authorize('viewAny', Service::class);
    }

    public function openCreateModal(): void
    {
        $this->authorize('create', Service::class);
        $this->resetForm();
        $this->showModal = true;
    }

    public function openEditModal(int $serviceId): void
    {
        $service = Service::findOrFail($serviceId);
        $this->authorize('update', $service);

        $this->editingId = $service->id;
        $this->name = $service->name;
        $this->category = $service->category->value;
        $this->price = (string) (float) $service->price;
        $this->durationMinutes = $service->duration_minutes;
        $this->isActive = $service->is_active;
        $this->resetValidation();
        $this->showModal = true;
    }

    public function save(
        CreateServiceAction $createAction,
        UpdateServiceAction $updateAction,
    ): void {
        $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'category' => ['required', Rule::enum(ServiceCategory::class)],
            'price' => ['required', 'numeric', 'min:0', 'max:9999999.99'],
            'durationMinutes' => ['required', 'integer', 'min:10', 'max:480'],
            'isActive' => ['boolean'],
        ]);

        $dto = new ServiceDTO(
            name: $this->name,
            category: ServiceCategory::from($this->category),
            price: (float) $this->price,
            durationMinutes: $this->durationMinutes,
            isActive: $this->isActive,
        );

        if ($this->editingId !== null) {
            $service = Service::findOrFail($this->editingId);
            $this->authorize('update', $service);
            $updateAction->execute($service, $dto);
            session()->flash('status', 'Услуга обновлена.');
        } else {
            $this->authorize('create', Service::class);
            $createAction->execute($dto);
            session()->flash('status', 'Услуга добавлена в справочник.');
        }

        $this->showModal = false;
        $this->resetForm();
    }

    public function toggleActive(int $serviceId, ToggleServiceActiveAction $action): void
    {
        $service = Service::findOrFail($serviceId);
        $this->authorize('toggleActive', $service);

        $updated = $action->execute($service);
        session()->flash('status', $updated->is_active ? 'Услуга возвращена в прайс.' : 'Услуга скрыта из прайса.');
    }

    private function resetForm(): void
    {
        $this->editingId = null;
        $this->name = '';
        $this->category = ServiceCategory::Therapy->value;
        $this->price = '0';
        $this->durationMinutes = 30;
        $this->isActive = true;
        $this->resetValidation();
    }

    public function render(): View
    {
        $services = Service::query()
            ->when($this->search !== '', function ($query): void {
                $term = '%' . mb_strtolower($this->search) . '%';
                $query->whereRaw('LOWER(name) LIKE ?', [$term]);
            })
            ->when($this->categoryFilter !== '', fn ($q) => $q->where('category', $this->categoryFilter))
            ->when($this->statusFilter === 'active', fn ($q) => $q->where('is_active', true))
            ->when($this->statusFilter === 'inactive', fn ($q) => $q->where('is_active', false))
            ->orderBy('category')
            ->orderBy('name')
            ->get();

        return view('catalog::livewire.service-index', [
            'services' => $services,
            'categories' => ServiceCategory::cases(),
            'canManage' => auth()->user()->can('create', Service::class),
        ]);
    }
}
