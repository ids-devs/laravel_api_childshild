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
