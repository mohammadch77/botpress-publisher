<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('contents', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('owner_id')->constrained('users')->cascadeOnDelete();
            $table->enum('type', ['article', 'note', 'media_post', 'thread'])->default('article');
            $table->string('title', 500)->nullable();
            $table->longText('body')->nullable();
            $table->enum('body_format', ['html', 'markdown', 'plain'])->default('html');
            $table->enum('status', ['draft', 'review', 'approved', 'archived'])->default('draft');
            $table->unsignedBigInteger('featured_asset_id')->nullable();
            $table->string('language', 10)->default('fa');
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('featured_asset_id')->references('id')->on('assets')->nullOnDelete();

            $table->index('tenant_id', 'idx_contents_tenant');
            $table->index('owner_id', 'idx_contents_owner');
            $table->index('status', 'idx_contents_status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contents');
    }
};
