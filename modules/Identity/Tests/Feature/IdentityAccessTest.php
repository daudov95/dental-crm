<?php

declare(strict_types=1);

namespace Modules\Identity\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Identity\Enums\UserRole;
use Modules\Identity\Models\User;
use Tests\TestCase;

class IdentityAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get('/')->assertRedirect('/login');
        $this->get('/staff')->assertRedirect('/login');
    }

    public function test_director_can_access_staff_page(): void
    {
        $director = User::create([
            'name' => 'Test Director',
            'email' => 'test.director@dentalcrm.local',
            'password' => 'password',
            'role' => UserRole::Director,
            'is_active' => true,
        ]);

        $this->actingAs($director)
            ->get('/staff')
            ->assertOk()
            ->assertSee('Сотрудники');
    }

    public function test_admin_and_doctor_cannot_access_staff_page(): void
    {
        $admin = User::create([
            'name' => 'Test Admin',
            'email' => 'test.admin@dentalcrm.local',
            'password' => 'password',
            'role' => UserRole::Admin,
            'is_active' => true,
        ]);

        $doctor = User::create([
            'name' => 'Test Doctor',
            'email' => 'test.doctor@dentalcrm.local',
            'password' => 'password',
            'role' => UserRole::Doctor,
            'is_active' => true,
        ]);

        $this->actingAs($admin)->get('/staff')->assertForbidden();
        $this->actingAs($doctor)->get('/staff')->assertForbidden();
    }
}
