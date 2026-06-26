<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            if (! Schema::hasColumn('products', 'resolved_kaspi_url')) {
                $table->text('resolved_kaspi_url')->nullable()->after('canonical_url');
            }
        });

        Schema::table('product_images', function (Blueprint $table) {
            if (! Schema::hasColumn('product_images', 'source_url')) {
                $table->text('source_url')->nullable()->after('path_webp');
            }
            if (! Schema::hasColumn('product_images', 'source_hash')) {
                $table->string('source_hash', 64)->nullable()->after('source_url');
                $table->index(['product_id', 'source_hash']);
            }
        });
    }

    public function down(): void
    {
        Schema::table('product_images', function (Blueprint $table) {
            if (Schema::hasColumn('product_images', 'source_hash')) {
                $table->dropIndex(['product_id', 'source_hash']);
                $table->dropColumn('source_hash');
            }
            if (Schema::hasColumn('product_images', 'source_url')) {
                $table->dropColumn('source_url');
            }
        });

        Schema::table('products', function (Blueprint $table) {
            if (Schema::hasColumn('products', 'resolved_kaspi_url')) {
                $table->dropColumn('resolved_kaspi_url');
            }
        });
    }
};
