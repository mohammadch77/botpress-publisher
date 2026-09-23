<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('platform_identities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('tenant_id')->nullable()->constrained('tenants')->nullOnDelete();
            $table->enum('platform', ['telegram', 'bale']);
            $table->string('external_user_id', 191);
            $table->string('external_username', 191)->nullable();
            $table->string('display_name', 191)->nullable();
            $table->string('language_code', 20)->nullable();
            $table->boolean('is_bot')->default(false);
            $table->boolean('is_verified')->default(false);
            $table->string('verification_code', 64)->nullable();
            $table->string('verification_token')->nullable();
            $table->timestamp('verified_at')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamp('last_interaction_at')->nullable();
            $table->timestamps();

            $table->unique(['platform', 'external_user_id'], 'uk_platform_identity');
            $table->index('user_id', 'idx_pi_user');
            $table->index('tenant_id', 'idx_pi_tenant');
            $table->index('platform', 'idx_pi_platform');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('platform_identities');
    }
};
