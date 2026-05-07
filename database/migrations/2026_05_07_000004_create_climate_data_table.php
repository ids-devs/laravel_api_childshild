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
