<?php

declare(strict_types=1);

namespace Modules\Catalog\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Modules\Catalog\Enums\ServiceCategory;
use Modules\Catalog\Livewire\ServiceIndex;
use Modules\Catalog\Models\Service;
use Modules\Identity\Enums\UserRole;
use Modules\Identity\Models\User;
use Tests\TestCase;

class CatalogTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_and_archive_service(): void
    {
        $admin = User::create([
            'name' => 'Test Admin',
            'email' => 'admin@test.local',
            'password' => 'password',
            'role' => UserRole::Admin,
            'is_active' => true,
        ]);

        Livewire::actingAs($admin)
            ->test(ServiceIndex::class)
            ->call('openCreateModal')
            ->set('name', 'Отбеливание Zoom 4')
            ->set('category', ServiceCategory::Therapy->value)
            ->set('price', '18000')
            ->set('durationMinutes', 60)
            ->call('save')
            ->assertHasNoErrors();

        $service = Service::where('name', 'Отбеливание Zoom 4')->firstOrFail();
        $this->assertTrue($service->is_active);

        Livewire::actingAs($admin)
            ->test(ServiceIndex::class)
            ->call('toggleActive', $service->id);

        $this->assertFalse($service->fresh()->is_active);
    }

    public function test_doctor_can_view_catalog_but_cannot_create_service(): void
    {
        $doctor = User::create([
            'name' => 'Test Doctor',
            'email' => 'doctor@test.local',
            'password' => 'password',
            'role' => UserRole::Doctor,
            'is_active' => true,
        ]);

        $this->actingAs($doctor)
            ->get('/services')
            ->assertOk()
            ->assertSee('Справочник услуг');

        Livewire::actingAs($doctor)
            ->test(ServiceIndex::class)
            ->call('openCreateModal')
            ->assertForbidden();
    }
}
