<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Seeds key location profiles mapped to province/district IDs.
 * Data sourced from INE, MISAU, CISM.
 */
class LocationsSeeder extends Seeder
{
    public function run(): void
    {
        $profiles = [
            // ── Maputo Cidade ────────────────────────────────────────────
            ['province' => 'Maputo Cidade', 'district' => 'KaMpfumo',     'locality' => null, 'latitude' => -25.9653, 'longitude' => 32.5892, 'malaria_risk_static' => 25, 'sanitation_score' => 65, 'flood_risk' => 30, 'air_quality_baseline' => 40, 'health_coverage_score' => 75, 'is_coastal' => true,  'is_urban' => true],
            ['province' => 'Maputo Cidade', 'district' => 'Nlhamankulu',   'locality' => null, 'latitude' => -25.9480, 'longitude' => 32.5727, 'malaria_risk_static' => 30, 'sanitation_score' => 45, 'flood_risk' => 35, 'air_quality_baseline' => 35, 'health_coverage_score' => 60, 'is_coastal' => false, 'is_urban' => true],
            ['province' => 'Maputo Cidade', 'district' => 'KaMaxaqueni',   'locality' => null, 'latitude' => -25.9312, 'longitude' => 32.5721, 'malaria_risk_static' => 35, 'sanitation_score' => 40, 'flood_risk' => 40, 'air_quality_baseline' => 30, 'health_coverage_score' => 55, 'is_coastal' => false, 'is_urban' => true],
            ['province' => 'Maputo Cidade', 'district' => 'KaMavota',      'locality' => null, 'latitude' => -25.9046, 'longitude' => 32.5962, 'malaria_risk_static' => 45, 'sanitation_score' => 30, 'flood_risk' => 55, 'air_quality_baseline' => 25, 'health_coverage_score' => 45, 'is_coastal' => false, 'is_urban' => true],
            ['province' => 'Maputo Cidade', 'district' => 'KaMubukwana',   'locality' => null, 'latitude' => -25.8825, 'longitude' => 32.5584, 'malaria_risk_static' => 50, 'sanitation_score' => 25, 'flood_risk' => 45, 'air_quality_baseline' => 20, 'health_coverage_score' => 40, 'is_coastal' => false, 'is_urban' => true],

            // ── Maputo Província ─────────────────────────────────────────
            ['province' => 'Maputo Província', 'district' => 'Matola',       'locality' => null, 'latitude' => -25.9620, 'longitude' => 32.4591, 'malaria_risk_static' => 40, 'sanitation_score' => 50, 'flood_risk' => 35, 'air_quality_baseline' => 45, 'health_coverage_score' => 60, 'is_coastal' => false, 'is_urban' => true],
            ['province' => 'Maputo Província', 'district' => 'Boane',         'locality' => null, 'latitude' => -26.0167, 'longitude' => 32.3333, 'malaria_risk_static' => 55, 'sanitation_score' => 30, 'flood_risk' => 40, 'air_quality_baseline' => 15, 'health_coverage_score' => 35, 'is_coastal' => false, 'is_urban' => false],
            ['province' => 'Maputo Província', 'district' => 'Manhiça',       'locality' => null, 'latitude' => -25.4000, 'longitude' => 32.8000, 'malaria_risk_static' => 75, 'sanitation_score' => 20, 'flood_risk' => 60, 'air_quality_baseline' => 10, 'health_coverage_score' => 45, 'is_coastal' => false, 'is_urban' => false],
            ['province' => 'Maputo Província', 'district' => 'Marracuene',    'locality' => null, 'latitude' => -25.7167, 'longitude' => 32.6833, 'malaria_risk_static' => 65, 'sanitation_score' => 25, 'flood_risk' => 70, 'air_quality_baseline' => 15, 'health_coverage_score' => 40, 'is_coastal' => true,  'is_urban' => false],

            // ── Gaza ──────────────────────────────────────────────────────
            ['province' => 'Gaza',           'district' => 'Xai-Xai',       'locality' => null, 'latitude' => -25.0500, 'longitude' => 33.6500, 'malaria_risk_static' => 70, 'sanitation_score' => 35, 'flood_risk' => 80, 'air_quality_baseline' => 10, 'health_coverage_score' => 45, 'is_coastal' => true,  'is_urban' => false],
            ['province' => 'Gaza',           'district' => 'Chókwè',         'locality' => null, 'latitude' => -24.5333, 'longitude' => 32.9833, 'malaria_risk_static' => 80, 'sanitation_score' => 15, 'flood_risk' => 90, 'air_quality_baseline' => 5,  'health_coverage_score' => 30, 'is_coastal' => false, 'is_urban' => false],
            ['province' => 'Gaza',           'district' => 'Chibuto',        'locality' => null, 'latitude' => -24.6833, 'longitude' => 33.5167, 'malaria_risk_static' => 75, 'sanitation_score' => 20, 'flood_risk' => 75, 'air_quality_baseline' => 8,  'health_coverage_score' => 35, 'is_coastal' => false, 'is_urban' => false],

            // ── Inhambane ─────────────────────────────────────────────────
            ['province' => 'Inhambane',      'district' => 'Inhambane Cidade', 'locality' => null, 'latitude' => -23.8650, 'longitude' => 35.3833, 'malaria_risk_static' => 60, 'sanitation_score' => 45, 'flood_risk' => 50, 'air_quality_baseline' => 20, 'health_coverage_score' => 50, 'is_coastal' => true,  'is_urban' => false],
            ['province' => 'Inhambane',      'district' => 'Maxixe',          'locality' => null, 'latitude' => -23.8667, 'longitude' => 35.3500, 'malaria_risk_static' => 65, 'sanitation_score' => 35, 'flood_risk' => 55, 'air_quality_baseline' => 15, 'health_coverage_score' => 45, 'is_coastal' => true,  'is_urban' => false],

            // ── Sofala ────────────────────────────────────────────────────
            ['province' => 'Sofala',         'district' => 'Beira',           'locality' => null, 'latitude' => -19.8436, 'longitude' => 34.8389, 'malaria_risk_static' => 70, 'sanitation_score' => 40, 'flood_risk' => 85, 'air_quality_baseline' => 30, 'health_coverage_score' => 55, 'is_coastal' => true,  'is_urban' => true],
            ['province' => 'Sofala',         'district' => 'Buzi',             'locality' => null, 'latitude' => -19.8667, 'longitude' => 34.7000, 'malaria_risk_static' => 75, 'sanitation_score' => 10, 'flood_risk' => 90, 'air_quality_baseline' => 5,  'health_coverage_score' => 25, 'is_coastal' => false, 'is_urban' => false],
            ['province' => 'Sofala',         'district' => 'Nhamatanda',       'locality' => null, 'latitude' => -19.6167, 'longitude' => 34.8333, 'malaria_risk_static' => 72, 'sanitation_score' => 15, 'flood_risk' => 88, 'air_quality_baseline' => 8,  'health_coverage_score' => 30, 'is_coastal' => false, 'is_urban' => false],

            // ── Manica ────────────────────────────────────────────────────
            ['province' => 'Manica',         'district' => 'Chimoio',          'locality' => null, 'latitude' => -19.1167, 'longitude' => 33.4833, 'malaria_risk_static' => 55, 'sanitation_score' => 45, 'flood_risk' => 40, 'air_quality_baseline' => 20, 'health_coverage_score' => 50, 'is_coastal' => false, 'is_urban' => false],

            // ── Tete ──────────────────────────────────────────────────────
            ['province' => 'Tete',           'district' => 'Tete Cidade',      'locality' => null, 'latitude' => -16.1564, 'longitude' => 33.5867, 'malaria_risk_static' => 65, 'sanitation_score' => 35, 'flood_risk' => 50, 'air_quality_baseline' => 35, 'health_coverage_score' => 45, 'is_coastal' => false, 'is_urban' => false],

            // ── Zambézia ──────────────────────────────────────────────────
            ['province' => 'Zambézia',       'district' => 'Quelimane',        'locality' => null, 'latitude' => -17.8786, 'longitude' => 36.8883, 'malaria_risk_static' => 80, 'sanitation_score' => 25, 'flood_risk' => 75, 'air_quality_baseline' => 10, 'health_coverage_score' => 40, 'is_coastal' => true,  'is_urban' => false],
            ['province' => 'Zambézia',       'district' => 'Mocuba',           'locality' => null, 'latitude' => -16.8333, 'longitude' => 36.9833, 'malaria_risk_static' => 85, 'sanitation_score' => 10, 'flood_risk' => 65, 'air_quality_baseline' => 5,  'health_coverage_score' => 20, 'is_coastal' => false, 'is_urban' => false],

            // ── Nampula ───────────────────────────────────────────────────
            ['province' => 'Nampula',        'district' => 'Nampula Cidade',   'locality' => null, 'latitude' => -15.1167, 'longitude' => 39.2667, 'malaria_risk_static' => 70, 'sanitation_score' => 30, 'flood_risk' => 55, 'air_quality_baseline' => 15, 'health_coverage_score' => 45, 'is_coastal' => false, 'is_urban' => false],
            ['province' => 'Nampula',        'district' => 'Nacala',           'locality' => null, 'latitude' => -14.5667, 'longitude' => 40.6833, 'malaria_risk_static' => 65, 'sanitation_score' => 35, 'flood_risk' => 50, 'air_quality_baseline' => 20, 'health_coverage_score' => 50, 'is_coastal' => true,  'is_urban' => false],

            // ── Cabo Delgado ──────────────────────────────────────────────
            ['province' => 'Cabo Delgado',   'district' => 'Pemba',            'locality' => null, 'latitude' => -12.9667, 'longitude' => 40.5167, 'malaria_risk_static' => 80, 'sanitation_score' => 25, 'flood_risk' => 45, 'air_quality_baseline' => 15, 'health_coverage_score' => 35, 'is_coastal' => true,  'is_urban' => false],

            // ── Niassa ────────────────────────────────────────────────────
            ['province' => 'Niassa',         'district' => 'Lichinga',         'locality' => null, 'latitude' => -13.3167, 'longitude' => 35.2333, 'malaria_risk_static' => 55, 'sanitation_score' => 30, 'flood_risk' => 30, 'air_quality_baseline' => 10, 'health_coverage_score' => 30, 'is_coastal' => false, 'is_urban' => false],
        ];

        $now = now();
        $provinceNamesById = DB::table('provinces')->pluck('name', 'id');
        $districts = DB::table('districts')->get(['id', 'name', 'province_id']);

        $profileMap = [];
        foreach ($profiles as $profile) {
            $profileMap[$profile['province'] . '|' . $profile['district']] = $profile;
        }

        $defaults = [
            'locality' => null,
            'latitude' => null,
            'longitude' => null,
            'malaria_risk_static' => 50,
            'sanitation_score' => 50,
            'flood_risk' => 50,
            'air_quality_baseline' => 50,
            'health_coverage_score' => 50,
            'is_coastal' => false,
            'is_urban' => false,
        ];

        $locations = [];
        foreach ($districts as $district) {
            $provinceName = $provinceNamesById[$district->province_id] ?? null;
            if (!$provinceName) {
                continue;
            }

            $profileKey = $provinceName . '|' . $district->name;
            $profile = $profileMap[$profileKey] ?? [];
            $data = array_merge($defaults, $profile);

            $locations[] = [
                'province_id' => $district->province_id,
                'district_id' => $district->id,
                'locality' => $data['locality'],
                'latitude' => $data['latitude'],
                'longitude' => $data['longitude'],
                'malaria_risk_static' => $data['malaria_risk_static'],
                'sanitation_score' => $data['sanitation_score'],
                'flood_risk' => $data['flood_risk'],
                'air_quality_baseline' => $data['air_quality_baseline'],
                'health_coverage_score' => $data['health_coverage_score'],
                'is_coastal' => $data['is_coastal'],
                'is_urban' => $data['is_urban'],
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        foreach ($locations as $location) {
            DB::table('locations')->updateOrInsert(
                [
                    'province_id' => $location['province_id'],
                    'district_id' => $location['district_id'],
                    'locality' => $location['locality'],
                ],
                $location
            );
        }

        // Sync PostGIS geom column
        DB::statement("
            UPDATE locations
            SET geom = ST_SetSRID(ST_MakePoint(longitude, latitude), 4326)
            WHERE latitude IS NOT NULL AND longitude IS NOT NULL AND geom IS NULL
        ");

        $this->command->info('[✓] ' . count($locations) . ' locations seeded.');
    }
}
