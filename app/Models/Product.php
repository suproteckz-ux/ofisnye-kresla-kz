<?php

namespace App\Models;

use App\Models\Traits\SeoMetaTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Builder;

class Product extends Model
{
    use SeoMetaTrait;

    protected $fillable = [
        'category_id', 'brand_id',
        'name', 'slug', 'sku',
        'price', 'old_price',
        'in_stock', 'quantity',
        'short_description', 'description', 'usage_instructions',
        'attributes', 'faq',
        'main_image', 'main_image_webp', 'main_image_alt',
        'meta_title', 'meta_description', 'h1', 'seo_text', 'canonical_url',
        'is_active', 'is_new', 'is_hit', 'is_popular',
        'views', 'sort_order',
    ];

    protected $casts = [
        'attributes' => 'array',
        'faq'        => 'array',
        'price'      => 'decimal:2',
        'old_price'  => 'decimal:2',
        'in_stock'   => 'boolean',
        'is_active'  => 'boolean',
        'is_new'     => 'boolean',
        'is_hit'     => 'boolean',
        'is_popular' => 'boolean',
    ];

    protected function defaultSeoTitle(): string
    {
        $name = $this->attributes['name'] ?? '';
        $price = number_format((float)($this->attributes['price'] ?? 0), 0, '.', ' ');
        return "{$name} — купить в Алматы за {$price} ₸ | " . config('app.name');
    }

    protected function defaultSeoDescription(): string
    {
        $name  = $this->attributes['name'] ?? '';
        $price = number_format((float)($this->attributes['price'] ?? 0), 0, '.', ' ');
        return "Купить {$name} в Алматы за {$price} ₸. Гарантия 12 мес. Доставка по Казахстану. Консультация бесплатно. Звоните!";
    }

    protected function defaultSeoH1(): string
    {
        return $this->attributes['name'] ?? '';
    }

    // Связи
    public function category(): BelongsTo { return $this->belongsTo(Category::class); }
    public function brand(): BelongsTo    { return $this->belongsTo(Brand::class); }
    public function images(): HasMany     { return $this->hasMany(ProductImage::class)->orderBy('sort_order'); }
    public function seoPages(): BelongsToMany  { return $this->belongsToMany(SeoPage::class, 'seo_page_product'); }
    public function blogPosts(): BelongsToMany { return $this->belongsToMany(BlogPost::class, 'blog_post_product'); }

    // Scopes
    public function scopeActive(Builder $q): Builder   { return $q->where('is_active', true); }
    public function scopeInStock(Builder $q): Builder  { return $q->where('in_stock', true); }
    public function scopeHits(Builder $q): Builder     { return $q->where('is_hit', true)->where('is_active', true); }
    public function scopeNew(Builder $q): Builder      { return $q->where('is_new', true)->where('is_active', true); }
    public function scopePopular(Builder $q): Builder  { return $q->where('is_popular', true)->where('is_active', true); }
    public function scopeInCategory(Builder $q, int $id): Builder { return $q->where('category_id', $id); }
    public function scopeByBrand(Builder $q, int $id): Builder    { return $q->where('brand_id', $id); }

    public function scopePriceBetween(Builder $q, ?float $min, ?float $max): Builder
    {
        if ($min !== null) $q->where('price', '>=', $min);
        if ($max !== null) $q->where('price', '<=', $max);
        return $q;
    }

    /**
     * Чистый SEO URL товара:
     *   /{parent.slug}/{category.slug}/{product.slug}  — если у категории есть родитель
     *   /{category.slug}/{product.slug}                — если категория корневая
     *   /product/{product.slug}                        — fallback если категории нет
     */
    public function getUrlAttribute(): string
    {
        $cat = $this->relationLoaded('category') ? $this->category : null;

        if (! $cat || ! array_key_exists('parent_id', $cat->getAttributes())) {
            $cat = $this->category()->with('parent:id,slug')->first();
            $this->setRelation('category', $cat);
        } elseif ($cat->parent_id && ! $cat->relationLoaded('parent')) {
            $cat->load('parent:id,slug');
        }

        if (! $cat) {
            // Fallback без категории — не попадает в sitemap
            return url('/product/' . $this->slug);
        }

        if ($cat->parent_id && $cat->relationLoaded('parent') && $cat->parent) {
            return url('/' . $cat->parent->slug . '/' . $cat->slug . '/' . $this->slug);
        }

        return url('/' . $cat->slug . '/' . $this->slug);
    }

    public function hasDiscount(): bool
    {
        return $this->old_price && $this->old_price > $this->price;
    }

    public function getDiscountPercentAttribute(): int
    {
        if (!$this->hasDiscount()) return 0;
        return (int) round((1 - $this->price / $this->old_price) * 100);
    }

    public function getWhatsappMessageAttribute(): string
    {
        $siteName = config('app.name');
        return urlencode("Здравствуйте! Хочу купить {$this->name} с сайта {$siteName}");
    }

    public function incrementViews(): void
    {
        static::where('id', $this->id)->increment('views');
    }

    /**
     * Безопасный getter для JSON-атрибутов кресла.
     * Возвращает массив даже если в БД хранится строка (старые записи или двойное кодирование).
     */
    public function getAttributesArrayAttribute(): array
    {
        $val = $this->getRawOriginal('attributes') ?? $this->attributes['attributes'] ?? null;
        if (is_array($val)) return $val;
        if (is_string($val) && $val !== '') {
            $decoded = json_decode($val, true);
            // Двойное кодирование: строка декодируется в строку снова
            if (is_string($decoded)) {
                $decoded = json_decode($decoded, true);
            }
            return is_array($decoded) ? $decoded : [];
        }
        return [];
    }

    /**
     * Безопасный getter для FAQ.
     * Возвращает массив даже если в БД хранится строка.
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
}
