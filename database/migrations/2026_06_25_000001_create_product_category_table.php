<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('product_category')) {
            Schema::create('product_category', function (Blueprint $table) {
                $table->id();
                $table->foreignId('product_id')->constrained()->cascadeOnDelete();
                $table->foreignId('category_id')->constrained()->cascadeOnDelete();
                $table->timestamps();

                $table->unique(['product_id', 'category_id']);
                $table->index('category_id');
            });
        }

        $now = now();

        DB::table('products')
            ->whereNotNull('category_id')
            ->select(['id', 'category_id'])
            ->orderBy('id')
            ->chunkById(500, function ($products) use ($now) {
                $rows = $products->map(fn ($product) => [
                    'product_id' => $product->id,
                    'category_id' => $product->category_id,
                    'created_at' => $now,
                    'updated_at' => $now,
                ])->all();

                if ($rows !== []) {
                    DB::table('product_category')->insertOrIgnore($rows);
                }
            });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_category');
    }
};
