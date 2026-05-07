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
            OrganizationTypeSeeder::class,
            RolesPermissionsSeeder::class,
            LocationsSeeder::class,
            AdminUserSeeder::class,
            UserHouseholdSeeder::class,
            HealthFacilitySeeder::class,
            ClimateDataSeeder::class,
            RiskScoreSeeder::class,
            AlertSeeder::class,
            CampaignSeeder::class,
            AlertDeliverySeeder::class,
            SymptomReportSeeder::class,
        ]);
    }
}
