<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('destinations', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->enum('type', ['wordpress_site', 'telegram_channel', 'telegram_group', 'bale_channel', 'bale_group']);
            $table->string('name', 191);
            $table->string('slug', 191)->nullable();
            $table->enum('status', ['active', 'inactive', 'error', 'pending'])->default('pending');
            $table->text('last_error')->nullable();
            $table->timestamp('last_error_at')->nullable();
            $table->timestamp('last_sync_at')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('tenant_id', 'idx_dest_tenant');
            $table->index('type', 'idx_dest_type');
            $table->index('status', 'idx_dest_status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('destinations');
    }
};
