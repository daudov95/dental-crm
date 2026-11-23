<?php

declare(strict_types=1);

namespace Modules\Identity\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Modules\Identity\Enums\UserRole;
use Modules\Identity\Models\User;

class IdentitySeeder extends Seeder
{
    public function run(): void
    {
        $staff = [
            [
                'name' => 'Гезуев Гималай (Руководитель)',
                'email' => 'director@dentalcrm.local',
                'role' => UserRole::Director,
                'specialization' => 'Главный врач',
            ],
            [
                'name' => 'Амина Исаева',
                'email' => 'admin@dentalcrm.local',
                'role' => UserRole::Admin,
                'specialization' => 'Старший администратор',
            ],
            [
                'name' => 'Мадина Ахмадова',
                'email' => 'orthodontist@dentalcrm.local',
                'role' => UserRole::Doctor,
                'specialization' => 'Врач-ортодонт',
            ],
            [
                'name' => 'Тимур Хасанов',
                'email' => 'surgeon@dentalcrm.local',
                'role' => UserRole::Doctor,
                'specialization' => 'Хирург-имплантолог',
            ],
            [
                'name' => 'Залина Мусаева',
                'email' => 'therapist@dentalcrm.local',
                'role' => UserRole::Doctor,
                'specialization' => 'Врач-терапевт',
            ],
        ];

        foreach ($staff as $member) {
            User::updateOrCreate(
                ['email' => $member['email']],
                [
                    'name' => $member['name'],
                    'password' => Hash::make('password'),
                    'role' => $member['role'],
                    'specialization' => $member['specialization'],
                    'is_active' => true,
                ]
            );
        }
    }
}
