<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('assets', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('owner_id')->constrained('users')->cascadeOnDelete();
            $table->enum('type', ['image', 'video', 'audio', 'document', 'other']);
            $table->string('mime_type', 100);
            $table->string('original_name', 255);
            $table->string('storage_disk', 50)->default('local');
            $table->string('storage_path', 500);
            $table->unsignedBigInteger('file_size');
            $table->unsignedInteger('width')->nullable();
            $table->unsignedInteger('height')->nullable();
            $table->unsignedInteger('duration')->nullable();
            $table->string('checksum', 64)->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('tenant_id', 'idx_assets_tenant');
            $table->index('owner_id', 'idx_assets_owner');
            $table->index('type', 'idx_assets_type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('assets');
    }
};
