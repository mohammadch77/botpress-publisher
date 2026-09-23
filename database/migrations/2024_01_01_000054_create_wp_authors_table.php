<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wp_authors', function (Blueprint $table) {
            $table->id();
            $table->foreignId('wordpress_site_id')->constrained('wordpress_sites')->cascadeOnDelete();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->integer('external_id');
            $table->string('name', 191);
            $table->string('slug', 191);
            $table->string('email', 191)->nullable();
            $table->string('role', 100)->nullable();
            $table->timestamp('synced_at')->nullable();

            $table->unique(['wordpress_site_id', 'external_id'], 'uk_wpauth_site_external');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wp_authors');
    }
};
