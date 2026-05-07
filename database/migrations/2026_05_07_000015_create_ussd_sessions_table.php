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
