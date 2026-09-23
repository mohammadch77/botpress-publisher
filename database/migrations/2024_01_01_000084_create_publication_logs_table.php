<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('publication_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('publication_id')->constrained('publications')->cascadeOnDelete();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->enum('level', ['debug', 'info', 'warning', 'error'])->default('info');
            $table->string('stage', 100);
            $table->text('message');
            $table->json('context')->nullable();
            $table->timestamp('created_at')->nullable();

            $table->index('publication_id', 'idx_pl_publication');
            $table->index('tenant_id', 'idx_pl_tenant');
            $table->index('level', 'idx_pl_level');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('publication_logs');
    }
};
