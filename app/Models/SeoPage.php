<?php

namespace App\Models;

use App\Models\Traits\SeoMetaTrait;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class SeoPage extends Model
{
    use SeoMetaTrait;

    protected $fillable = [
        'title','slug','h1','meta_title','meta_description','seo_text','faq','is_active',
    ];

    protected $casts = [
        'faq'       => 'array',
        'is_active' => 'boolean',
    ];

    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'seo_page_product');
    }

    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(Category::class, 'seo_page_category');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function getUrlAttribute(): string
    {
        return url("/{$this->slug}");
    }

    protected function defaultSeoTitle(): string
    {
        return "{$this->title} | " . config('app.name');
    }

    protected function defaultSeoDescription(): string
    {
        return $this->title;
    }

    protected function defaultSeoH1(): string
    {
        return $this->h1 ?: $this->title;
    }
}
