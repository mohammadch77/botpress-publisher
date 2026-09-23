<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('destination_users', function (Blueprint $table) {
            $table->id();
            $table->foreignId('destination_id')->constrained('destinations')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->enum('permission', ['view', 'publish', 'manage'])->default('publish');
            $table->unsignedBigInteger('granted_by')->nullable();
            $table->timestamp('granted_at')->useCurrent();

            $table->unique(['destination_id', 'user_id'], 'uk_du_destination_user');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('destination_users');
    }
};
