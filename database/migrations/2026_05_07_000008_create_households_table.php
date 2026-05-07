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
