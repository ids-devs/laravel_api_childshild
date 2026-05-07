<?php

namespace Database\Seeders;

use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RiskTypeSeeder extends Seeder
{
    public function run(): void
    {
        $now = Carbon::now();
        $riskTypes = [
            ['name' => 'Heat', 'code' => 'heat', 'description' => 'Heat stress and high temperature risk'],
            ['name' => 'Malaria', 'code' => 'malaria', 'description' => 'Malaria transmission risk'],
            ['name' => 'Diarrhea', 'code' => 'diarrhea', 'description' => 'Diarrheal disease risk'],
            ['name' => 'Respiratory', 'code' => 'respiratory', 'description' => 'Respiratory illness risk'],
        ];

        foreach ($riskTypes as $riskType) {
            DB::table('risk_types')->updateOrInsert(
                ['code' => $riskType['code']],
                ['name' => $riskType['name'], 'description' => $riskType['description'], 'updated_at' => $now, 'created_at' => $now]
            );
        }
    }
}
