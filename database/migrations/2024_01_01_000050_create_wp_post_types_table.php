<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wp_post_types', function (Blueprint $table) {
            $table->id();
            $table->foreignId('wordpress_site_id')->constrained('wordpress_sites')->cascadeOnDelete();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->string('slug', 100);
            $table->string('label', 191)->nullable();
            $table->json('supports')->nullable();
            $table->boolean('is_public')->default(true);
            $table->boolean('has_archive')->default(false);
            $table->timestamp('created_at')->nullable();

            $table->unique(['wordpress_site_id', 'slug'], 'uk_wpt_site_slug');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wp_post_types');
    }
};
