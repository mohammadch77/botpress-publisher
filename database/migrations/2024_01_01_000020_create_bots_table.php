<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bots', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->enum('platform', ['telegram', 'bale']);
            $table->string('name', 191);
            $table->string('username', 191)->nullable();
            $table->text('token_encrypted');
            $table->string('token_hash', 64);
            $table->string('webhook_url', 500)->nullable();
            $table->string('webhook_secret')->nullable();
            $table->enum('status', ['active', 'inactive', 'error', 'pending'])->default('pending');
            $table->text('last_error')->nullable();
            $table->timestamp('last_error_at')->nullable();
            $table->timestamp('last_webhook_at')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('tenant_id', 'idx_bots_tenant');
            $table->index('platform', 'idx_bots_platform');
            $table->index('status', 'idx_bots_status');
            $table->index('token_hash', 'idx_bots_token_hash');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bots');
    }
};
