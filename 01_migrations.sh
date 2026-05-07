#!/usr/bin/env bash
# =============================================================================
# ChildShield Climate AI — Migration Files Generator
# Generates all migration files for the PostgreSQL + PostGIS schema
# Run from the Laravel project root
# =============================================================================
set -e

echo "========================================"
echo " Generating Migrations..."
echo "========================================"

TIMESTAMP=$(date +%Y_%m_%d)
COUNTER=0

next_ts() {
  COUNTER=$((COUNTER + 1))
  printf '%s_%06d' "$TIMESTAMP" "$COUNTER"
}

# ─── 0. PostGIS extension ────────────────────────────────────────────────────
cat > "database/migrations/$(next_ts)_enable_postgis.php" << 'MIGRATION'
<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        DB::statement('CREATE EXTENSION IF NOT EXISTS postgis');
        DB::statement('CREATE EXTENSION IF NOT EXISTS pgcrypto');
    }
    public function down(): void
    {
        DB::statement('DROP EXTENSION IF EXISTS postgis CASCADE');
        DB::statement('DROP EXTENSION IF EXISTS pgcrypto CASCADE');
    }
};
MIGRATION

# ─── 1. Provinces ─────────────────────────────────────────────────────────────
cat > "database/migrations/$(next_ts)_create_provinces_table.php" << 'MIGRATION'
<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('provinces', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('region');
            $table->timestamps();
            $table->softDeletes();
        });
    }
    public function down(): void
    {
        Schema::dropIfExists('provinces');
    }
};
MIGRATION

# ─── 2. Districts ─────────────────────────────────────────────────────────────
cat > "database/migrations/$(next_ts)_create_districts_table.php" << 'MIGRATION'
<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('districts', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->foreignId('province_id')->constrained('provinces')->cascadeOnDelete();
            $table->timestamps();
            $table->softDeletes();
            $table->unique(['province_id', 'name']);
        });
    }
    public function down(): void
    {
        Schema::dropIfExists('districts');
    }
};
MIGRATION

# ─── 3. Risk Types ────────────────────────────────────────────────────────────
cat > "database/migrations/$(next_ts)_create_risk_types_table.php" << 'MIGRATION'
<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('risk_types', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('code')->unique();
            $table->text('description')->nullable();
            $table->timestamps();
        });
    }
    public function down(): void
    {
        Schema::dropIfExists('risk_types');
    }
};
MIGRATION

# ─── 4. Locations ─────────────────────────────────────────────────────────────
cat > "database/migrations/$(next_ts)_create_locations_table.php" << 'MIGRATION'
<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('locations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('province_id')->constrained('provinces')->cascadeOnDelete();
            $table->foreignId('district_id')->constrained('districts')->cascadeOnDelete();
            $table->string('locality')->nullable();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            // PostGIS geometry column added below
            $table->decimal('malaria_risk_static', 5, 2)->default(0);
            $table->decimal('sanitation_score', 5, 2)->default(0);
            $table->decimal('flood_risk', 5, 2)->default(0);
            $table->decimal('air_quality_baseline', 5, 2)->default(0);
            $table->decimal('health_coverage_score', 5, 2)->default(0);
            $table->boolean('is_coastal')->default(false);
            $table->boolean('is_urban')->default(false);
            $table->timestamps();
            $table->unique(['province_id', 'district_id', 'locality']);
            $table->index(['province_id', 'district_id']);
        });

        // Add PostGIS geometry column
        DB::statement('ALTER TABLE locations ADD COLUMN IF NOT EXISTS geom geometry(Point, 4326)');
        DB::statement('CREATE INDEX IF NOT EXISTS locations_geom_idx ON locations USING GIST(geom)');
        DB::statement("
            CREATE OR REPLACE FUNCTION sync_location_geom()
            RETURNS TRIGGER AS \$\$
            BEGIN
                IF NEW.latitude IS NOT NULL AND NEW.longitude IS NOT NULL THEN
                    NEW.geom = ST_SetSRID(ST_MakePoint(NEW.longitude, NEW.latitude), 4326);
                END IF;
                RETURN NEW;
            END;
            \$\$ LANGUAGE plpgsql;
        ");
        DB::statement("
            DROP TRIGGER IF EXISTS trg_sync_location_geom ON locations;
            CREATE TRIGGER trg_sync_location_geom
            BEFORE INSERT OR UPDATE ON locations
            FOR EACH ROW EXECUTE FUNCTION sync_location_geom();
        ");
    }
    public function down(): void
    {
        Schema::dropIfExists('locations');
    }
};
MIGRATION

# ─── 5. Users (families via USSD/SMS/WhatsApp) ──────────────────────────────
cat > "database/migrations/$(next_ts)_create_users_table.php" << 'MIGRATION'
<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            // Phone stored encrypted (AES-256 via pgcrypto)
            $table->text('phone_number_encrypted');
            $table->string('phone_hash', 64)->unique(); // SHA-256 for lookup
            $table->enum('channel', ['ussd', 'sms', 'whatsapp'])->default('ussd');
            $table->enum('language', ['pt', 'changane', 'sena', 'macua', 'ndau'])->default('pt');
            $table->foreignId('location_id')->nullable()->constrained('locations')->nullOnDelete();
            $table->boolean('consent_status')->default(false);
            $table->boolean('subscription_active')->default(false);
            $table->timestamp('consent_given_at')->nullable();
            $table->timestamp('last_interaction_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->index('phone_hash');
            $table->index(['location_id', 'subscription_active']);
        });
    }
    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
MIGRATION

# ─── 6. Clinic Users (dashboard users) ──────────────────────────────────────
cat > "database/migrations/$(next_ts)_create_clinic_users_table.php" << 'MIGRATION'
<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('clinic_users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('password');
            $table->string('organization_name')->nullable();
            $table->enum('organization_type', ['clinic', 'ong', 'government', 'unicef', 'admin'])->default('clinic');
            $table->foreignId('location_id')->nullable()->constrained('locations')->nullOnDelete();
            $table->boolean('is_active')->default(true);
            $table->string('remember_token', 100)->nullable();
            $table->timestamp('email_verified_at')->nullable();
            $table->timestamp('last_login_at')->nullable();
            $table->string('last_login_ip', 45)->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->index(['email', 'is_active']);
        });
    }
    public function down(): void
    {
        Schema::dropIfExists('clinic_users');
    }
};
MIGRATION

# ─── 7. Households ───────────────────────────────────────────────────────────
cat > "database/migrations/$(next_ts)_create_households_table.php" << 'MIGRATION'
<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('households', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->unsignedTinyInteger('number_of_children')->default(0);
            // JSON: ['0-1', '1-5', '6-12']
            $table->json('children_age_groups')->nullable();
            $table->boolean('pregnant_woman')->default(false);
            $table->enum('weeks_pregnant', ['1-12', '13-24', '25-40'])->nullable();
            $table->decimal('vulnerability_score', 5, 2)->default(0);
            $table->timestamps();
            $table->index('user_id');
        });
    }
    public function down(): void
    {
        Schema::dropIfExists('households');
    }
};
MIGRATION

# ─── 8. Climate Data ─────────────────────────────────────────────────────────
cat > "database/migrations/$(next_ts)_create_climate_data_table.php" << 'MIGRATION'
<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('climate_data', function (Blueprint $table) {
            $table->id();
            $table->foreignId('location_id')->constrained('locations')->cascadeOnDelete();
            $table->decimal('temperature', 5, 2)->nullable();
            $table->decimal('temperature_max', 5, 2)->nullable();
            $table->decimal('temperature_min', 5, 2)->nullable();
            $table->decimal('rainfall_24h', 8, 2)->nullable();
            $table->decimal('humidity', 5, 2)->nullable();
            $table->decimal('wind_speed', 6, 2)->nullable();
            $table->decimal('air_quality_index', 6, 2)->nullable();
            $table->enum('source', ['openweather', 'tomorrow', 'inam'])->default('openweather');
            $table->timestamp('recorded_at');
            $table->boolean('is_forecast')->default(false);
            $table->timestamps();
            $table->index(['location_id', 'recorded_at']);
            $table->index(['source', 'recorded_at']);
        });
    }
    public function down(): void
    {
        Schema::dropIfExists('climate_data');
    }
};
MIGRATION

# ─── 9. Risk Scores ──────────────────────────────────────────────────────────
cat > "database/migrations/$(next_ts)_create_risk_scores_table.php" << 'MIGRATION'
<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('risk_scores', function (Blueprint $table) {
            $table->id();
            $table->foreignId('location_id')->constrained('locations')->cascadeOnDelete();
            $table->foreignId('risk_type_id')->constrained('risk_types')->cascadeOnDelete();
            $table->enum('risk_level', ['low', 'medium', 'high', 'critical']);
            $table->decimal('score', 5, 2);
            $table->text('recommendation')->nullable();
            $table->json('factors')->nullable(); // raw factor breakdown
            $table->timestamp('calculated_at');
            $table->timestamps();
            $table->unique(['location_id', 'risk_type_id', 'calculated_at']);
            $table->index(['location_id', 'risk_type_id', 'risk_level']);
        });
    }
    public function down(): void
    {
        Schema::dropIfExists('risk_scores');
    }
};
MIGRATION

# ─── 10. Alerts ──────────────────────────────────────────────────────────────
cat > "database/migrations/$(next_ts)_create_alerts_table.php" << 'MIGRATION'
<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('alerts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('location_id')->constrained('locations')->cascadeOnDelete();
            $table->foreignId('risk_score_id')->nullable()->constrained('risk_scores')->nullOnDelete();
            $table->foreignId('risk_type_id')->constrained('risk_types')->cascadeOnDelete();
            $table->enum('risk_level', ['low', 'medium', 'high', 'critical']);
            $table->text('message_pt');
            $table->text('message_changane')->nullable();
            $table->text('message_sena')->nullable();
            $table->text('message_macua')->nullable();
            $table->text('message_ndau')->nullable();
            $table->enum('channel', ['sms', 'whatsapp', 'both'])->default('both');
            $table->enum('status', ['pending', 'processing', 'sent', 'failed', 'cancelled'])->default('pending');
            $table->unsignedInteger('recipients_total')->default(0);
            $table->unsignedInteger('recipients_sent')->default(0);
            $table->unsignedInteger('recipients_failed')->default(0);
            $table->json('delivery_report')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('scheduled_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('clinic_users')->nullOnDelete();
            $table->timestamps();
            $table->index(['location_id', 'status']);
            $table->index(['risk_level', 'status']);
        });
    }
    public function down(): void
    {
        Schema::dropIfExists('alerts');
    }
};
MIGRATION

# ─── 11. Alert Deliveries ─────────────────────────────────────────────────────
cat > "database/migrations/$(next_ts)_create_alert_deliveries_table.php" << 'MIGRATION'
<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('alert_deliveries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('alert_id')->constrained('alerts')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->enum('channel', ['sms', 'whatsapp']);
            $table->enum('status', ['queued', 'sent', 'delivered', 'failed'])->default('queued');
            $table->string('provider_message_id')->nullable();
            $table->text('failure_reason')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamps();
            $table->index(['alert_id', 'status']);
            $table->index(['user_id', 'created_at']);
        });
    }
    public function down(): void
    {
        Schema::dropIfExists('alert_deliveries');
    }
};
MIGRATION

# ─── 12. Symptom Reports ──────────────────────────────────────────────────────
cat > "database/migrations/$(next_ts)_create_symptom_reports_table.php" << 'MIGRATION'
<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('symptom_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('location_id')->constrained('locations')->cascadeOnDelete();
            // JSON: ['fever', 'diarrhea', 'cough', 'vomiting', etc.]
            $table->json('symptoms');
            $table->text('notes')->nullable();
            $table->enum('channel', ['whatsapp', 'ussd', 'dashboard'])->default('whatsapp');
            $table->timestamps();
            $table->index(['location_id', 'created_at']);
        });
    }
    public function down(): void
    {
        Schema::dropIfExists('symptom_reports');
    }
};
MIGRATION

# ─── 13. USSD Sessions ───────────────────────────────────────────────────────
cat > "database/migrations/$(next_ts)_create_ussd_sessions_table.php" << 'MIGRATION'
<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('ussd_sessions', function (Blueprint $table) {
            $table->id();
            $table->string('session_id', 100)->unique();
            $table->string('phone_hash', 64);
            $table->string('service_code', 20);
            $table->string('current_step', 50)->default('main_menu');
            $table->json('session_data')->nullable(); // temp state during flow
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->index(['session_id', 'is_active']);
        });
    }
    public function down(): void
    {
        Schema::dropIfExists('ussd_sessions');
    }
};
MIGRATION

# ─── 14. Campaigns ───────────────────────────────────────────────────────────
cat > "database/migrations/$(next_ts)_create_campaigns_table.php" << 'MIGRATION'
<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('campaigns', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('message');
            $table->enum('channel', ['sms', 'whatsapp', 'both'])->default('sms');
            $table->json('target_provinces')->nullable(); // null = all
            $table->json('target_districts')->nullable();
            $table->enum('target_risk_level', ['medium', 'high', 'critical', 'all'])->default('all');
            $table->enum('status', ['draft', 'scheduled', 'sending', 'sent', 'cancelled'])->default('draft');
            $table->unsignedInteger('recipients_total')->default(0);
            $table->unsignedInteger('recipients_sent')->default(0);
            $table->timestamp('scheduled_at')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->foreignId('created_by')->constrained('clinic_users')->cascadeOnDelete();
            $table->timestamps();
            $table->index(['status', 'scheduled_at']);
        });
    }
    public function down(): void
    {
        Schema::dropIfExists('campaigns');
    }
};
MIGRATION

# ─── 15. Health Facilities ───────────────────────────────────────────────────
cat > "database/migrations/$(next_ts)_create_health_facilities_table.php" << 'MIGRATION'
<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('health_facilities', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->enum('type', ['hospital', 'health_center', 'health_post', 'clinic']);
            $table->foreignId('location_id')->constrained('locations')->cascadeOnDelete();
            $table->string('address')->nullable();
            $table->string('phone', 20)->nullable();
            $table->decimal('latitude', 10, 7);
            $table->decimal('longitude', 10, 7);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        DB::statement('ALTER TABLE health_facilities ADD COLUMN IF NOT EXISTS geom geometry(Point, 4326)');
        DB::statement('CREATE INDEX IF NOT EXISTS health_facilities_geom_idx ON health_facilities USING GIST(geom)');
        DB::statement("
            CREATE TRIGGER trg_sync_facility_geom
            BEFORE INSERT OR UPDATE ON health_facilities
            FOR EACH ROW EXECUTE FUNCTION sync_location_geom();
        ");
    }
    public function down(): void
    {
        Schema::dropIfExists('health_facilities');
    }
};
MIGRATION

# ─── 16. Activity Logs ───────────────────────────────────────────────────────
cat > "database/migrations/$(next_ts)_create_activity_logs_table.php" << 'MIGRATION'
<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('activity_logs', function (Blueprint $table) {
            $table->id();
            $table->string('log_name')->default('default');
            $table->text('description');
            $table->nullableMorphs('subject');
            $table->nullableMorphs('causer');
            $table->json('properties')->nullable();
            $table->string('batch_uuid', 36)->nullable();
            $table->timestamp('created_at')->nullable();
        });
    }
    public function down(): void
    {
        Schema::dropIfExists('activity_logs');
    }
};
MIGRATION

# ─── 17. Seeders ──────────────────────────────────────────────────────────────
cat > "database/seeders/ProvinceSeeder.php" << 'SEEDER'
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
SEEDER

cat > "database/seeders/DistrictSeeder.php" << 'SEEDER'
<?php

namespace Database\Seeders;

use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DistrictSeeder extends Seeder
{
    public function run(): void
    {
        $now = Carbon::now();
        $provinceMap = DB::table('provinces')->pluck('id', 'name');

        $districts = [
            'Maputo Cidade' => ['KaMpfumo', 'Nlhamankulu', 'KaMaxakeni', 'KaMavota', 'KaMubukwana', 'KaTembe', 'KaNyaka'],
            'Maputo Província' => ['Matola', 'Boane', 'Marracuene', 'Namaacha', 'Moamba', 'Manhiça', 'Magude', 'Matutuíne'],
            'Gaza' => ['Xai-Xai', 'Chókwè', 'Chibuto', 'Mandlakazi', 'Bilene', 'Guijá', 'Mabalane', 'Chicualacuala', 'Chigubo', 'Massangena', 'Massingir', 'Limpopo', 'Mapai'],
            'Inhambane' => ['Inhambane', 'Maxixe', 'Vilankulo', 'Massinga', 'Morrumbene', 'Homoine', 'Inharrime', 'Zavala', 'Jangamo', 'Funhalouro', 'Panda', 'Mabote', 'Govuro'],
            'Manica' => ['Chimoio', 'Manica', 'Gondola', 'Sussundenga', 'Bárue', 'Mossurize', 'Macossa', 'Tambara', 'Machaze', 'Guro', 'Macate', 'Vanduzi'],
            'Sofala' => ['Beira', 'Dondo', 'Nhamatanda', 'Búzi', 'Gorongosa', 'Marromeu', 'Caia', 'Chemba', 'Cheringoma', 'Machanga', 'Muanza', 'Chibabava', 'Maríngue'],
            'Tete' => ['Tete', 'Moatize', 'Angónia', 'Changara', 'Cahora-Bassa', 'Mutarara', 'Tsangano', 'Chiuta', 'Marávia', 'Macanga', 'Chifunde', 'Dôa', 'Zumbo', 'Marara'],
            'Zambézia' => ['Quelimane', 'Alto Molócue', 'Chinde', 'Gilé', 'Gurué', 'Ile', 'Inhassunge', 'Lugela', 'Maganja da Costa', 'Milange', 'Mocuba', 'Mopeia', 'Morrumbala', 'Namacurra', 'Namarroi', 'Nicoadala', 'Pebane', 'Derre', 'Luabo', 'Mocubela', 'Molumbo', 'Mulevala'],
            'Nampula' => ['Nampula', 'Angoche', 'Eráti', 'Ilha de Moçambique', 'Lalaua', 'Malema', 'Meconta', 'Mecubúri', 'Memba', 'Mogincual', 'Mogovolas', 'Moma', 'Monapo', 'Mossuril', 'Muecate', 'Murrupula', 'Nacala-a-Velha', 'Nacala-Porto', 'Nacarôa', 'Rapale', 'Ribaué', 'Liúpo'],
            'Cabo Delgado' => ['Pemba', 'Chiúre', 'Montepuez', 'Mocímboa da Praia', 'Macomia', 'Mueda', 'Muidumbe', 'Nangade', 'Palma', 'Ancuabe', 'Balama', 'Mecúfi', 'Meluco', 'Namuno', 'Quissanga', 'Ibo', 'Metuge'],
            'Niassa' => ['Lichinga', 'Cuamba', 'Mandimba', 'Marrupa', 'Majune', 'Mecanhelas', 'Mecula', 'Metarica', 'Muembe', 'N\'gauma', 'Nipepe', 'Sanga', 'Chimbonila', 'Lago', 'Mavago'],
        ];

        foreach ($districts as $provinceName => $items) {
            $provinceId = $provinceMap[$provinceName] ?? null;
            if (!$provinceId) {
                continue;
            }

            foreach ($items as $districtName) {
                DB::table('districts')->updateOrInsert(
                    ['province_id' => $provinceId, 'name' => $districtName],
                    ['updated_at' => $now, 'created_at' => $now]
                );
            }
        }
    }
}
SEEDER

cat > "database/seeders/RiskTypeSeeder.php" << 'SEEDER'
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
SEEDER

cat > "database/seeders/DatabaseSeeder.php" << 'SEEDER'
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
        ]);
    }
}
SEEDER

# ─── 18. Spatie permission tables ─────────────────────────────────────────────
echo "[+] Note: Spatie permission migration is published separately via vendor:publish"

echo ""
echo "[✓] All migration files generated in database/migrations/"
echo "[!] Run: php artisan migrate --step to apply them"
