<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kaspi_photo_import_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->nullable()->constrained('products')->nullOnDelete();
            $table->string('sku')->nullable()->index();
            $table->text('product_url')->nullable();
            $table->boolean('kaspi_button_found')->default(false);
            $table->boolean('kaspi_widget_opened')->default(false);
            $table->text('resolved_kaspi_url')->nullable();
            $table->boolean('kaspi_page_loaded')->default(false);
            $table->unsignedInteger('photos_found')->default(0);
            $table->unsignedInteger('photos_downloaded')->default(0);
            $table->unsignedInteger('photos_saved')->default(0);
            $table->unsignedInteger('duplicates_skipped')->default(0);
            $table->boolean('main_image_changed')->default(false);
            $table->string('status')->default('pending')->index();
            $table->text('error_message')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kaspi_photo_import_logs');
    }
};
