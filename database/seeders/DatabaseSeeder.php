<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            ProvinceSeeder::class,
            DistrictSeeder::class,
            RiskTypeSeeder::class,
            RolesPermissionsSeeder::class,
            LocationsSeeder::class,
            AdminUserSeeder::class,
        ]);
    }
}
