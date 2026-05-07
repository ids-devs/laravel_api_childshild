<?php

namespace Database\Seeders;

use App\Models\HealthFacility;
use App\Models\Location;
use Illuminate\Database\Seeder;

class HealthFacilitySeeder extends Seeder
{
    public function run(): void
    {
        $locations = Location::query()->with(['district'])->limit(12)->get();
        $types = ['hospital', 'health_center', 'health_post', 'clinic'];

        foreach ($locations as $index => $location) {
            $name = 'Health Facility ' . ($index + 1);
            $type = $types[$index % count($types)];

            HealthFacility::query()->updateOrCreate(
                ['name' => $name, 'location_id' => $location->id],
                [
                    'type' => $type,
                    'address' => ($location->district?->name ?? 'Unknown District') . ', Mozambique',
                    'phone' => '+25821300' . str_pad((string) ($index + 1), 3, '0', STR_PAD_LEFT),
                    'latitude' => $location->latitude ?? (-25 + ($index / 10)),
                    'longitude' => $location->longitude ?? (32 + ($index / 10)),
                    'is_active' => true,
                ]
            );
        }
    }
}
