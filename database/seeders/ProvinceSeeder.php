<?php

namespace Database\Seeders;

use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ProvinceSeeder extends Seeder
{
    public function run(): void
    {
        $now = Carbon::now();
        $provinces = [
            ['name' => 'Maputo Cidade', 'region' => 'Sul'],
            ['name' => 'Maputo Província', 'region' => 'Sul'],
            ['name' => 'Gaza', 'region' => 'Sul'],
            ['name' => 'Inhambane', 'region' => 'Sul'],
            ['name' => 'Manica', 'region' => 'Centro'],
            ['name' => 'Sofala', 'region' => 'Centro'],
            ['name' => 'Tete', 'region' => 'Centro'],
            ['name' => 'Zambézia', 'region' => 'Centro'],
            ['name' => 'Nampula', 'region' => 'Norte'],
            ['name' => 'Cabo Delgado', 'region' => 'Norte'],
            ['name' => 'Niassa', 'region' => 'Norte'],
        ];

        foreach ($provinces as $province) {
            DB::table('provinces')->updateOrInsert(
                ['name' => $province['name']],
                ['region' => $province['region'], 'updated_at' => $now, 'created_at' => $now]
            );
        }
    }
}
