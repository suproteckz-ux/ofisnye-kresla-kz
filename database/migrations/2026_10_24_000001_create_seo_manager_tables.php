<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('seo_manager_requests', function (Blueprint $table) {
            $table->id();
            $table->string('actor', 24);
            $table->string('idempotency_key', 128);
            $table->string('payload_hash', 64);
            $table->json('response')->nullable();
            $table->unsignedSmallInteger('http_status')->default(201);
            $table->timestamps();
            $table->unique(['actor', 'idempotency_key']);
        });
        Schema::create('seo_manager_drafts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('blog_post_id')->unique()->constrained('blog_posts')->cascadeOnDelete();
            $table->json('metadata');
            $table->timestamps();
        });
        Schema::create('seo_manager_proposals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('blog_post_id')->constrained('blog_posts')->cascadeOnDelete();
            $table->json('original');
            $table->json('proposal');
            $table->string('status', 16)->default('proposed');
            $table->timestamps();
            $table->index(['blog_post_id', 'status']);
        });
        Schema::create('seo_manager_audit_logs', function (Blueprint $table) {
            $table->id();
            $table->string('actor', 24);
            $table->string('action', 32);
            $table->unsignedBigInteger('resource_id');
            $table->string('payload_hash', 64);
            $table->timestamps();
            $table->index(['resource_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('seo_manager_audit_logs');
        Schema::dropIfExists('seo_manager_proposals');
        Schema::dropIfExists('seo_manager_drafts');
        Schema::dropIfExists('seo_manager_requests');
    }
};
