<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('conversation_sessions', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('bot_id')->constrained('bots')->cascadeOnDelete();
            $table->foreignId('platform_identity_id')->constrained('platform_identities')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->string('chat_id', 191);
            $table->enum('chat_type', ['private', 'group', 'supergroup', 'channel'])->default('private');
            $table->string('current_flow', 100)->nullable();
            $table->string('current_step', 100)->nullable();
            $table->json('context')->nullable();
            $table->bigInteger('message_id')->nullable();
            $table->enum('status', ['active', 'completed', 'cancelled', 'expired'])->default('active');
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();

            $table->index(['bot_id', 'chat_id'], 'idx_cs_bot_chat');
            $table->index('status', 'idx_cs_status');
            $table->index('expires_at', 'idx_cs_expires');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('conversation_sessions');
    }
};
