<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Phone stored encrypted (AES-256 via pgcrypto)
            if (! Schema::hasColumn('users', 'phone_number_encrypted')) {
                $table->text('phone_number_encrypted')->nullable();
            }
            if (! Schema::hasColumn('users', 'phone_hash')) {
                $table->string('phone_hash', 64)->nullable()->unique();
            }
            if (! Schema::hasColumn('users', 'channel')) {
                $table->enum('channel', ['ussd', 'sms', 'whatsapp'])->default('ussd');
            }
            if (! Schema::hasColumn('users', 'language')) {
                $table->enum('language', ['pt', 'changane', 'sena', 'macua', 'ndau'])->default('pt');
            }
            if (! Schema::hasColumn('users', 'location_id')) {
                $table->foreignId('location_id')->nullable()->constrained('locations')->nullOnDelete();
            }
            if (! Schema::hasColumn('users', 'consent_status')) {
                $table->boolean('consent_status')->default(false);
            }
            if (! Schema::hasColumn('users', 'subscription_active')) {
                $table->boolean('subscription_active')->default(false);
            }
            if (! Schema::hasColumn('users', 'consent_given_at')) {
                $table->timestamp('consent_given_at')->nullable();
            }
            if (! Schema::hasColumn('users', 'last_interaction_at')) {
                $table->timestamp('last_interaction_at')->nullable();
            }
            if (! Schema::hasColumn('users', 'deleted_at')) {
                $table->softDeletes();
            }
        });

        Schema::table('users', function (Blueprint $table) {
            $table->index('phone_hash');
            $table->index(['location_id', 'subscription_active']);
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'location_id')) {
                $table->dropConstrainedForeignId('location_id');
            }

            $columns = [
                'phone_number_encrypted',
                'phone_hash',
                'channel',
                'language',
                'consent_status',
                'subscription_active',
                'consent_given_at',
                'last_interaction_at',
                'deleted_at',
            ];

            foreach ($columns as $column) {
                if (Schema::hasColumn('users', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
