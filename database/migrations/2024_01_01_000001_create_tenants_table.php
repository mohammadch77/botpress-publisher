<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tenants', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('name', 191);
            $table->string('slug', 100)->unique();
            $table->enum('plan', ['free', 'pro', 'enterprise'])->default('free');
            $table->enum('status', ['active', 'suspended', 'trial', 'cancelled'])->default('active');
            $table->json('settings')->nullable();
            $table->timestamp('trial_ends_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('slug', 'idx_tenants_slug');
            $table->index('status', 'idx_tenants_status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tenants');
    }
};
