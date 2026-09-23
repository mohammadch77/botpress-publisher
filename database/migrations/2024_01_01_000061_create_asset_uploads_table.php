<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('asset_uploads', function (Blueprint $table) {
            $table->id();
            $table->foreignId('asset_id')->constrained('assets')->cascadeOnDelete();
            $table->foreignId('destination_id')->constrained('destinations')->cascadeOnDelete();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->string('external_id', 191)->nullable();
            $table->string('external_url', 500)->nullable();
            $table->enum('status', ['pending', 'uploaded', 'failed'])->default('pending');
            $table->timestamp('uploaded_at')->nullable();
            $table->text('last_error')->nullable();

            $table->unique(['asset_id', 'destination_id'], 'uk_au_asset_destination');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('asset_uploads');
    }
};
