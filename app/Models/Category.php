<?php

namespace App\Models;

use App\Models\Traits\SeoMetaTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Builder;

class Category extends Model
{
    use SeoMetaTrait;

    protected $fillable = [
        'parent_id', 'name', 'slug',
        'image', 'image_webp',
        'meta_title', 'meta_description', 'h1',
        'seo_text_top', 'seo_text_bottom', 'canonical_url',
        'faq', 'is_active', 'sort_order',
    ];

    protected $casts = [
        'faq'       => 'array',
        'is_active' => 'boolean',
    ];

    protected function defaultSeoTitle(): string
    {
        $name = $this->attributes['name'] ?? '';
        return "{$name} в Алматы — купить с доставкой | " . config('app.name');
    }

    protected function defaultSeoDescription(): string
    {
        $name = $this->attributes['name'] ?? '';
        return "Купить {$name} в Алматы. Большой выбор, доставка по Казахстану. Консультация бесплатно. Гарантия 12 месяцев.";
    }

    protected function defaultSeoH1(): string
    {
        return ($this->attributes['name'] ?? '') . ' в Алматы';
    }

    // Связи
    public function parent(): BelongsTo  { return $this->belongsTo(Category::class, 'parent_id'); }
    public function children(): HasMany  { return $this->hasMany(Category::class, 'parent_id')->orderBy('sort_order'); }
    public function allChildren(): HasMany { return $this->children()->with('allChildren'); }
    public function products(): HasMany  { return $this->hasMany(Product::class); }
    public function linkedProducts(): BelongsToMany { return $this->belongsToMany(Product::class, 'product_category')->withTimestamps(); }
    public function seoPages(): BelongsToMany { return $this->belongsToMany(SeoPage::class, 'seo_page_category'); }
    public function seoFilters(): HasMany     { return $this->hasMany(SeoFilter::class); }

    // Scopes
    public function scopeActive(Builder $q): Builder  { return $q->where('is_active', true); }
    public function scopeRoot(Builder $q): Builder    { return $q->whereNull('parent_id'); }
    public function scopeOrdered(Builder $q): Builder { return $q->orderBy('sort_order')->orderBy('name'); }

    public function isRoot(): bool { return is_null($this->parent_id); }

    /**
     * Безопасный getter для FAQ категории.
     * Обрабатывает двойное кодирование и строки.
     */
    public function getFaqArrayAttribute(): array
    {
        $val = $this->getRawOriginal('faq') ?? $this->attributes['faq'] ?? null;
        if (is_array($val)) return $val;
        if (is_string($val) && $val !== '') {
            $decoded = json_decode($val, true);
            if (is_string($decoded)) {
                $decoded = json_decode($decoded, true);
            }
            return is_array($decoded) ? $decoded : [];
        }
        return [];
    }

    public function getUrlAttribute(): string
    {
        if ($this->parent) {
            return url('/' . $this->parent->slug . '/' . $this->slug);
        }
        return url('/' . $this->slug);
    }
}
