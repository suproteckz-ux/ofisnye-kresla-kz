<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('marketradar_sync_logs', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('product_id')->nullable()->constrained('products')->nullOnDelete();
            $table->string('sku')->nullable()->index();
            $table->string('offer_id')->nullable()->index();
            $table->string('vendor_code')->nullable()->index();
            $table->string('matched_by')->nullable()->index();
            $table->string('status')->index();
            $table->decimal('old_price', 10, 2)->nullable();
            $table->decimal('new_price', 10, 2)->nullable();
            $table->unsignedInteger('old_quantity')->nullable();
            $table->unsignedInteger('new_quantity')->nullable();
            $table->boolean('old_available')->nullable();
            $table->boolean('new_available')->nullable();
            $table->unsignedInteger('photos_found')->default(0);
            $table->unsignedInteger('photos_saved')->default(0);
            $table->unsignedInteger('duplicates_skipped')->default(0);
            $table->text('source_url')->nullable();
            $table->text('error_message')->nullable();
            $table->boolean('dry_run')->default(false)->index();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->timestamps();

            $table->index(['product_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('marketradar_sync_logs');
    }
};
