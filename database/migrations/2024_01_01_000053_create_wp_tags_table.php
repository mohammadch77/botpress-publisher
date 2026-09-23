<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wp_tags', function (Blueprint $table) {
            $table->id();
            $table->foreignId('wordpress_site_id')->constrained('wordpress_sites')->cascadeOnDelete();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->integer('external_id');
            $table->string('name', 191);
            $table->string('slug', 191);
            $table->unsignedInteger('post_count')->default(0);
            $table->timestamp('synced_at')->nullable();

            $table->unique(['wordpress_site_id', 'external_id'], 'uk_wptag_site_external');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wp_tags');
    }
};
