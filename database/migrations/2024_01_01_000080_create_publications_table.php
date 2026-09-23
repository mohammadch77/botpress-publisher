<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('publications', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->uuid('idempotency_key')->unique();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('content_id')->constrained('contents')->cascadeOnDelete();
            $table->foreignId('destination_id')->constrained('destinations')->cascadeOnDelete();
            $table->foreignId('created_by')->constrained('users');
            $table->enum('status', [
                'draft', 'pending', 'scheduled', 'processing', 'published', 'failed', 'cancelled', 'partial',
            ])->default('draft');

            $table->unsignedBigInteger('depends_on_publication_id')->nullable();
            $table->string('dependency_field', 100)->nullable();

            $table->timestamp('scheduled_at')->nullable();
            $table->timestamp('published_at')->nullable();

            $table->unsignedTinyInteger('max_attempts')->default(3);
            $table->unsignedTinyInteger('attempts')->default(0);
            $table->timestamp('last_attempt_at')->nullable();
            $table->timestamp('next_retry_at')->nullable();
            $table->text('last_error')->nullable();
            $table->string('last_error_code', 100)->nullable();

            $table->string('external_id', 191)->nullable();
            $table->string('external_url', 500)->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->foreign('depends_on_publication_id')->references('id')->on('publications')->nullOnDelete();

            $table->index('tenant_id', 'idx_pub_tenant');
            $table->index('status', 'idx_pub_status');
            $table->index('scheduled_at', 'idx_pub_scheduled');
            $table->index('next_retry_at', 'idx_pub_next_retry');
            $table->index('content_id', 'idx_pub_content');
            $table->index('destination_id', 'idx_pub_destination');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('publications');
    }
};
