<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wp_elementor_templates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('wordpress_site_id')->constrained('wordpress_sites')->cascadeOnDelete();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->integer('external_id');
            $table->string('name', 255);
            $table->string('type', 100)->nullable();
            $table->json('conditions')->nullable();
            $table->json('post_types')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamp('synced_at')->nullable();

            $table->unique(['wordpress_site_id', 'external_id'], 'uk_wpet_site_external');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wp_elementor_templates');
    }
};
