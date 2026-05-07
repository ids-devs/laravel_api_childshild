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
