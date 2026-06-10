<?php

namespace App\Models;

use App\Models\Traits\SeoMetaTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Builder;

class Brand extends Model
{
    use SeoMetaTrait;

    protected $fillable = [
        'name', 'slug', 'logo', 'logo_webp', 'description',
        'meta_title', 'meta_description', 'h1', 'canonical_url',
        'is_active', 'sort_order',
    ];

    protected $casts = ['is_active' => 'boolean'];

    protected function defaultSeoTitle(): string
    {
        $name = $this->attributes['name'] ?? '';
        return "Кресла {$name} в Алматы — купить | " . config('app.name');
    }

    protected function defaultSeoDescription(): string
    {
        $name = $this->attributes['name'] ?? '';
        return "Офисные кресла {$name} в Алматы. Большой выбор, гарантия 12 месяцев, доставка по Казахстану.";
    }

    protected function defaultSeoH1(): string
    {
        $name = $this->attributes['name'] ?? '';
        return "Офисные кресла {$name} в Алматы";
    }

    public function products(): HasMany { return $this->hasMany(Product::class); }
    public function seoFilters(): HasMany { return $this->hasMany(SeoFilter::class); }

    public function scopeActive(Builder $q): Builder  { return $q->where('is_active', true); }
    public function scopeOrdered(Builder $q): Builder { return $q->orderBy('sort_order')->orderBy('name'); }

    public function getUrlAttribute(): string
    {
        return route('brand.show', ['brand' => $this->slug]);
    }
}
