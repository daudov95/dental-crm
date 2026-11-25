<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Modules\Audit\Database\Seeders\AuditSeeder;
use Modules\Billing\Database\Seeders\BillingSeeder;
use Modules\Catalog\Database\Seeders\CatalogSeeder;
use Modules\Deals\Database\Seeders\DealsSeeder;
use Modules\Identity\Database\Seeders\IdentitySeeder;
use Modules\Patients\Database\Seeders\PatientsSeeder;
use Modules\Scheduling\Database\Seeders\SchedulingSeeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        $this->call([
            IdentitySeeder::class,
            CatalogSeeder::class,
            PatientsSeeder::class,
            DealsSeeder::class,
            SchedulingSeeder::class,
            BillingSeeder::class,
            AuditSeeder::class,
        ]);
    }
}
