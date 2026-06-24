<?php

namespace App\Models;

use App\Models\Traits\SeoMetaTrait;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Collection;

class SeoPage extends Model
{
    use SeoMetaTrait;

    protected $fillable = [
        'title','slug','h1',
        'hero_image','hero_subtitle','hero_button_text','hero_button_url',
        'meta_title','meta_description','seo_text','faq',
        'cta_title','cta_text','cta_button_text','cta_button_url',
        'is_active',
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

    public function relatedLandingProducts(int $limit = 8): Collection
    {
        $products = $this->relationLoaded('products')
            ? $this->products
            : $this->products()->active()->with(['brand', 'category.parent'])->get();

        $products = $products->filter(fn (Product $product) => $product->is_active)->take($limit)->values();

        if ($products->isNotEmpty()) {
            return $products;
        }

        $categoryIds = ($this->relationLoaded('categories') ? $this->categories : $this->categories()->get())
            ->pluck('id')
            ->filter()
            ->values();

        if ($categoryIds->isEmpty()) {
            return collect();
        }

        return Product::active()
            ->whereIn('category_id', $categoryIds)
            ->with([
                'brand',
                'category:id,name,slug,parent_id',
                'category.parent:id,slug',
            ])
            ->orderByDesc('is_hit')
            ->orderByDesc('is_popular')
            ->orderBy('sort_order')
            ->limit($limit)
            ->get();
    }

    public function heroImagePath(?Collection $relatedProducts = null): ?string
    {
        if (!empty($this->hero_image)) {
            return $this->hero_image;
        }

        $product = ($relatedProducts ?? $this->relatedLandingProducts(1))
            ->first(fn (Product $item) => !empty($item->main_image));

        if ($product) {
            return $product->main_image;
        }

        $category = ($this->relationLoaded('categories') ? $this->categories : $this->categories()->get())
            ->first(fn (Category $item) => !empty($item->image));

        return $category?->image;
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
