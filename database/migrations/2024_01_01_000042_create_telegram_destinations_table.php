<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('telegram_destinations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('destination_id')->unique()->constrained('destinations')->cascadeOnDelete();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('bot_id')->constrained('bots')->cascadeOnDelete();
            $table->string('external_chat_id', 191);
            $table->string('title', 255)->nullable();
            $table->string('username', 191)->nullable();
            $table->string('invite_link', 500)->nullable();
            $table->unsignedInteger('member_count')->nullable();
            $table->boolean('bot_is_admin')->default(false);
            $table->json('permissions')->nullable();
            $table->timestamps();

            $table->index('tenant_id', 'idx_tg_dest_tenant');
            $table->index('bot_id', 'idx_tg_dest_bot');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('telegram_destinations');
    }
};
