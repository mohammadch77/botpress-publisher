<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('conversation_history', function (Blueprint $table) {
            $table->id();
            $table->foreignId('session_id')->constrained('conversation_sessions')->cascadeOnDelete();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->enum('direction', ['incoming', 'outgoing']);
            $table->string('message_type', 50);
            $table->string('external_message_id', 191)->nullable();
            $table->text('content')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamp('created_at')->nullable();

            $table->index('session_id', 'idx_ch_session');
            $table->index('tenant_id', 'idx_ch_tenant');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('conversation_history');
    }
};
