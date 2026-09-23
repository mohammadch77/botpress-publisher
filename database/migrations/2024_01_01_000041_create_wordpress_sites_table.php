<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wordpress_sites', function (Blueprint $table) {
            $table->id();
            $table->foreignId('destination_id')->unique()->constrained('destinations')->cascadeOnDelete();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->string('url', 500);
            $table->text('api_key_encrypted');
            $table->string('api_key_hash', 64);
            $table->string('plugin_version', 20)->nullable();
            $table->string('wp_version', 20)->nullable();
            $table->boolean('has_elementor')->default(false);
            $table->boolean('has_rank_math')->default(false);
            $table->boolean('has_yoast')->default(false);
            $table->string('seo_plugin', 50)->nullable();
            $table->json('site_profile')->nullable();
            $table->json('content_profile')->nullable();
            $table->timestamp('discovery_completed_at')->nullable();
            $table->enum('discovery_status', ['pending', 'running', 'completed', 'failed'])->default('pending');
            $table->timestamps();

            $table->index('tenant_id', 'idx_wp_sites_tenant');
        });

        if (DB::connection()->getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE wordpress_sites ADD INDEX idx_wp_sites_url (url(191))');
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('wordpress_sites');
    }
};
