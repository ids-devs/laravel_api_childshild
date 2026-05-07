<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('generated_reports', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->date('period_start');
            $table->date('period_end');
            $table->string('province');
            $table->string('district')->nullable();
            $table->enum('report_type', ['weekly', 'monthly', 'custom']);
            $table->enum('format', ['pdf', 'excel']);
            $table->enum('status', ['generating', 'ready', 'failed'])->default('generating');
            $table->string('download_url')->nullable();
            $table->unsignedInteger('alerts_count')->default(0);
            $table->unsignedInteger('families_covered')->default(0);
            $table->text('error_message')->nullable();
            $table->foreignId('location_id')->nullable()->constrained('locations')->nullOnDelete();
            $table->foreignId('created_by')->constrained('clinic_users')->cascadeOnDelete();
            $table->timestamps();

            $table->index(['created_by', 'status']);
            $table->index(['location_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('generated_reports');
    }
};
