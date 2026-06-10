<?php

namespace App\Models;

use App\Models\Traits\SeoMetaTrait;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class BlogPost extends Model
{
    use SeoMetaTrait;

    protected $fillable = [
        'title','slug','h1','meta_title','meta_description',
        'cover_image','cover_image_webp','cover_image_alt',
        'content','faq','is_active','published_at',
    ];

    protected $casts = [
        'faq'          => 'array',
        'is_active'    => 'boolean',
        'published_at' => 'datetime',
    ];

    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'blog_post_product');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true)
                     ->where('published_at', '<=', now());
    }

    public function getUrlAttribute(): string
    {
        return url("/blog/{$this->slug}");
    }

    protected function defaultSeoTitle(): string
    {
        return "{$this->title} | Блог " . config('app.name');
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
