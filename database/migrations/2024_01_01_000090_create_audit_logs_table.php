<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->nullable();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('event', 191);
            $table->string('auditable_type', 191)->nullable();
            $table->unsignedBigInteger('auditable_id')->nullable();
            $table->json('old_values')->nullable();
            $table->json('new_values')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamp('created_at')->nullable();

            $table->index('tenant_id', 'idx_al_tenant');
            $table->index('user_id', 'idx_al_user');
            $table->index('event', 'idx_al_event');
            $table->index(['auditable_type', 'auditable_id'], 'idx_al_auditable');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
    }
};
