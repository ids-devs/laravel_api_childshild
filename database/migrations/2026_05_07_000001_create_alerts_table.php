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
