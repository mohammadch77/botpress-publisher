<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->string('name', 191);
            $table->string('email', 191)->nullable()->unique();
            $table->string('password')->nullable();
            $table->enum('status', ['active', 'inactive', 'banned'])->default('active');
            $table->timestamp('last_seen_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('tenant_id', 'idx_users_tenant');
            $table->index('status', 'idx_users_status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
