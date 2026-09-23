<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('content_assets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('content_id')->constrained('contents')->cascadeOnDelete();
            $table->foreignId('asset_id')->constrained('assets')->cascadeOnDelete();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->enum('role', ['featured', 'inline', 'attachment', 'gallery'])->default('inline');
            $table->unsignedSmallInteger('sort_order')->default(0);

            $table->unique(['content_id', 'asset_id', 'role'], 'uk_ca_content_asset_role');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('content_assets');
    }
};
