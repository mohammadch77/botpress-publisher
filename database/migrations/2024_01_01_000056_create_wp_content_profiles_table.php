<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wp_content_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('wordpress_site_id')->constrained('wordpress_sites')->cascadeOnDelete();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->string('name', 191);
            $table->string('post_type', 100)->default('post');
            $table->foreignId('category_id')->nullable()->constrained('wp_categories')->nullOnDelete();
            $table->foreignId('template_id')->nullable()->constrained('wp_elementor_templates')->nullOnDelete();
            $table->json('required_fields')->nullable();
            $table->json('recommended_structure')->nullable();
            $table->json('seo_config')->nullable();
            $table->unsignedInteger('avg_word_count')->nullable();
            $table->unsignedInteger('sample_count')->default(0);
            $table->boolean('is_default')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wp_content_profiles');
    }
};
