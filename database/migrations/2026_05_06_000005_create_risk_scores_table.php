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
