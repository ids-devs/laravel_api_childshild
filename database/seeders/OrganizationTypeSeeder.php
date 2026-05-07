<?php

namespace Database\Seeders;

use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class OrganizationTypeSeeder extends Seeder
{
    public function run(): void
    {
        $now = Carbon::now();
        $organizationTypes = [
            ['name' => 'Clinic', 'code' => 'clinic'],
            ['name' => 'ONG', 'code' => 'ong'],
            ['name' => 'Government', 'code' => 'government'],
            ['name' => 'UNICEF', 'code' => 'unicef'],
            ['name' => 'Admin', 'code' => 'admin'],
        ];

        foreach ($organizationTypes as $organizationType) {
            DB::table('organization_types')->updateOrInsert(
                ['code' => $organizationType['code']],
                ['name' => $organizationType['name'], 'updated_at' => $now, 'created_at' => $now]
            );
        }
    }
}
