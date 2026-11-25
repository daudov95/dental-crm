<?php

declare(strict_types=1);

namespace Modules\Catalog\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Catalog\Enums\ServiceCategory;
use Modules\Catalog\Models\Service;

class CatalogSeeder extends Seeder
{
    public function run(): void
    {
        $services = [
            ['name' => 'Первичная консультация и осмотр', 'category' => ServiceCategory::Diagnostics, 'price' => 0, 'duration_minutes' => 30],
            ['name' => 'Компьютерная томография (КТ) челюсти', 'category' => ServiceCategory::Diagnostics, 'price' => 2500, 'duration_minutes' => 20],
            ['name' => 'Лечение кариеса (световая пломба)', 'category' => ServiceCategory::Therapy, 'price' => 4500, 'duration_minutes' => 60],
            ['name' => 'Профессиональная гигиена полости рта', 'category' => ServiceCategory::Therapy, 'price' => 4000, 'duration_minutes' => 45],
            ['name' => 'Лечение пульпита (эндодонтия)', 'category' => ServiceCategory::Therapy, 'price' => 8500, 'duration_minutes' => 90],
            ['name' => 'Установка металлической брекет-системы (2 челюсти)', 'category' => ServiceCategory::Orthodontics, 'price' => 95000, 'duration_minutes' => 90],
            ['name' => 'Установка керамической брекет-системы (2 челюсти)', 'category' => ServiceCategory::Orthodontics, 'price' => 135000, 'duration_minutes' => 90],
            ['name' => 'Плановая коррекция и активация брекетов', 'category' => ServiceCategory::Orthodontics, 'price' => 3500, 'duration_minutes' => 30],
            ['name' => 'Установка импланта (хирургический этап)', 'category' => ServiceCategory::Implantology, 'price' => 35000, 'duration_minutes' => 60],
            ['name' => 'Сложное удаление зуба мудрости', 'category' => ServiceCategory::Surgery, 'price' => 6500, 'duration_minutes' => 45],
            ['name' => 'Коронка из диоксида циркония', 'category' => ServiceCategory::Prosthetics, 'price' => 22000, 'duration_minutes' => 60],
            ['name' => 'Керамический винир (1 единица)', 'category' => ServiceCategory::Prosthetics, 'price' => 28000, 'duration_minutes' => 60],
        ];

        foreach ($services as $item) {
            Service::updateOrCreate(
                ['name' => $item['name']],
                [
                    'category' => $item['category'],
                    'price' => $item['price'],
                    'duration_minutes' => $item['duration_minutes'],
                    'is_active' => true,
                ]
            );
        }
    }
}
