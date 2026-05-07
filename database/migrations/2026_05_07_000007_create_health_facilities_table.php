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
