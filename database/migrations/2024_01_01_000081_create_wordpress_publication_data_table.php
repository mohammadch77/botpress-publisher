<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wordpress_publication_data', function (Blueprint $table) {
            $table->id();
            $table->foreignId('publication_id')->unique()->constrained('publications')->cascadeOnDelete();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();

            $table->string('title', 500)->nullable();
            $table->string('slug', 500)->nullable();
            $table->text('excerpt')->nullable();
            $table->longText('content')->nullable();
            $table->enum('post_status', ['publish', 'draft', 'pending', 'private', 'future'])->default('publish');
            $table->string('post_type', 100)->default('post');

            $table->json('wp_category_ids')->nullable();
            $table->json('wp_tag_ids')->nullable();
            $table->integer('wp_author_id')->nullable();
            $table->integer('wp_template_id')->nullable();
            $table->unsignedBigInteger('featured_image_asset_id')->nullable();

            $table->string('seo_title', 500)->nullable();
            $table->text('seo_description')->nullable();
            $table->string('focus_keyword', 255)->nullable();
            $table->string('canonical_url', 500)->nullable();
            $table->string('robots', 100)->nullable();

            $table->longText('elementor_data')->nullable();

            $table->integer('external_post_id')->nullable();
            $table->string('external_url', 500)->nullable();

            $table->foreign('featured_image_asset_id')->references('id')->on('assets')->nullOnDelete();

            $table->index('tenant_id', 'idx_wpd_tenant');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wordpress_publication_data');
    }
};
