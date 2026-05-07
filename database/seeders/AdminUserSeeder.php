<?php

namespace Database\Seeders;

use App\Models\ClinicUser;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * Seeds the initial super-admin user and sample users per role.
 * Credentials are read from environment or defaults (CHANGE IN PRODUCTION).
 */
class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        // ─── Super Admin ────────────────────────────────────────────────
        $superAdmin = ClinicUser::firstOrCreate(
            ['email' => env('ADMIN_EMAIL', 'admin@childshield.mz')],
            [
                'name'              => 'ChildShield Admin',
                'password'          => Hash::make(env('ADMIN_PASSWORD', 'ChildShield@2025!')),
                'organization_name' => 'ChildShield Climate AI',
                'organization_type' => 'admin',
                'is_active'         => true,
                'email_verified_at' => now(),
            ]
        );
        $superAdmin->assignRole('super-admin');

        // ─── Sample Clinic User ─────────────────────────────────────────
        $clinic = ClinicUser::firstOrCreate(
            ['email' => 'clinica.maputo@demo.mz'],
            [
                'name'              => 'Centro de Saúde KaMpfumo',
                'password'          => Hash::make('Clinica@2025!'),
                'organization_name' => 'CS KaMpfumo',
                'organization_type' => 'clinic',
                'location_id'       => \App\Models\Location::query()
                    ->whereHas('district', fn($q) => $q->where('name', 'KaMpfumo'))
                    ->value('id'),
                'is_active'         => true,
                'email_verified_at' => now(),
            ]
        );
        $clinic->assignRole('clinic');

        // ─── Sample ONG User ────────────────────────────────────────────
        $ong = ClinicUser::firstOrCreate(
            ['email' => 'ong.gaza@demo.mz'],
            [
                'name'              => 'ONG Saúde Gaza',
                'password'          => Hash::make('Ong@2025!'),
                'organization_name' => 'Saúde para Todos',
                'organization_type' => 'ong',
                'location_id'       => \App\Models\Location::query()
                    ->whereHas('district', fn($q) => $q->where('name', 'Xai-Xai'))
                    ->value('id'),
                'is_active'         => true,
                'email_verified_at' => now(),
            ]
        );
        $ong->assignRole('ong');

        // ─── Sample Government User ─────────────────────────────────────
        $gov = ClinicUser::firstOrCreate(
            ['email' => 'misau@demo.mz'],
            [
                'name'              => 'MISAU - Direcção Nacional',
                'password'          => Hash::make('Misau@2025!'),
                'organization_name' => 'MISAU',
                'organization_type' => 'government',
                'is_active'         => true,
                'email_verified_at' => now(),
            ]
        );
        $gov->assignRole('government');

        $this->command->info('[✓] Admin and sample users seeded.');
        $this->command->warn('⚠  IMPORTANT: Change default passwords before deploying to production!');
    }
}
