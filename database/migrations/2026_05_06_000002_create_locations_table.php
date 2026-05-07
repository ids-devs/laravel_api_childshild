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

        // Enable PostGIS only when the extension exists on the PostgreSQL server.
        $canInstallPostgis = DB::table('pg_available_extensions')
            ->where('name', 'postgis')
            ->exists();

        if ($canInstallPostgis) {
            DB::statement('CREATE EXTENSION IF NOT EXISTS postgis');
        }

        $hasGeometryType = DB::table('pg_type')->where('typname', 'geometry')->exists();

        if ($hasGeometryType) {
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
            DB::statement('DROP TRIGGER IF EXISTS trg_sync_location_geom ON locations');
            DB::statement("
                CREATE TRIGGER trg_sync_location_geom
                BEFORE INSERT OR UPDATE ON locations
                FOR EACH ROW EXECUTE FUNCTION sync_location_geom()
            ");
        }
    }
    public function down(): void
    {
        DB::statement('DROP FUNCTION IF EXISTS sync_location_geom()');
        Schema::dropIfExists('locations');
    }
};
