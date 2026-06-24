<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('seo_pages', function (Blueprint $table) {
            $table->string('hero_image')->nullable()->after('h1');
            $table->text('hero_subtitle')->nullable()->after('hero_image');
            $table->string('hero_button_text')->nullable()->after('hero_subtitle');
            $table->string('hero_button_url')->nullable()->after('hero_button_text');
            $table->string('cta_title')->nullable()->after('faq');
            $table->text('cta_text')->nullable()->after('cta_title');
            $table->string('cta_button_text')->nullable()->after('cta_text');
            $table->string('cta_button_url')->nullable()->after('cta_button_text');
        });
    }

    public function down(): void
    {
        Schema::table('seo_pages', function (Blueprint $table) {
            $table->dropColumn([
                'hero_image',
                'hero_subtitle',
                'hero_button_text',
                'hero_button_url',
                'cta_title',
                'cta_text',
                'cta_button_text',
                'cta_button_url',
            ]);
        });
    }
};
