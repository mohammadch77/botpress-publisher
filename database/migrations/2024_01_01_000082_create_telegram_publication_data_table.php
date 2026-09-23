<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('telegram_publication_data', function (Blueprint $table) {
            $table->id();
            $table->foreignId('publication_id')->unique()->constrained('publications')->cascadeOnDelete();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();

            $table->text('message_text')->nullable();
            $table->text('caption')->nullable();
            $table->enum('parse_mode', ['HTML', 'Markdown', 'MarkdownV2'])->nullable()->default('HTML');
            $table->unsignedBigInteger('media_asset_id')->nullable();
            $table->enum('media_type', ['photo', 'video', 'document', 'audio', 'animation'])->nullable();
            $table->boolean('disable_web_page_preview')->default(false);
            $table->boolean('disable_notification')->default(false);
            $table->bigInteger('reply_to_message_id')->nullable();
            $table->json('inline_keyboard')->nullable();

            $table->bigInteger('external_message_id')->nullable();

            $table->foreign('media_asset_id')->references('id')->on('assets')->nullOnDelete();

            $table->index('tenant_id', 'idx_tgd_tenant');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('telegram_publication_data');
    }
};
