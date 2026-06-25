<?php

namespace App\Models;

use App\Models\Traits\SeoMetaTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

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

    public function seoTitle(): string
    {
        $stored = $this->attributes['meta_title'] ?? null;

        if ($stored && ! $this->isSpammySeoTitle($stored)) {
            return $this->limitSeoTitle($this->cleanSeoTitle($stored));
        }

        return $this->defaultSeoTitle();
    }

    protected function defaultSeoTitle(): string
    {
        $name = $this->cleanProductTitleName($this->attributes['name'] ?? '');
        $suffix = ' — купить в Алматы | Офисные кресла';
        $maxNameLength = max(20, 70 - mb_strlen($suffix));

        return Str::limit($name, $maxNameLength, '') . $suffix;
    }

    public function hasProblemSeoTitle(): bool
    {
        return $this->seoTitleAuditReasons() !== [];
    }

    public function seoTitleAuditReasons(): array
    {
        $title = $this->seoTitle();
        $reasons = [];

        if ($title === '') {
            $reasons[] = 'empty';
        }
        if (mb_strlen($title) > 70) {
            $reasons[] = 'too_long';
        }
        if ($this->hasRepeatedSeoPhrase($title)) {
            $reasons[] = 'repeated_phrase';
        }
        if ($this->hasForbiddenSeoPhrase($title)) {
            $reasons[] = 'forbidden_phrase';
        }

        return $reasons;
    }

    private function isSpammySeoTitle(string $title): bool
    {
        return mb_strlen($title) > 70
            || $this->hasRepeatedSeoPhrase($title)
            || $this->hasForbiddenSeoPhrase($title);
    }

    private function cleanSeoTitle(string $title): string
    {
        $title = strip_tags($title);
        $title = preg_replace('/\s+/u', ' ', $title) ?? $title;
        $title = str_replace([' | | ', ' — — '], [' | ', ' — '], $title);

        return trim($title);
    }

    private function cleanProductTitleName(string $name): string
    {
        $name = $this->cleanSeoTitle($name);
        $name = preg_replace('/\s+(купить|заказать)\s+в\s+алматы.*$/iu', '', $name) ?? $name;
        $name = preg_replace('/\s+где\s+выгодно\s+приобрести.*$/iu', '', $name) ?? $name;

        return trim($name) ?: 'Офисное кресло';
    }

    private function limitSeoTitle(string $title): string
    {
        if (mb_strlen($title) <= 70) {
            return $title;
        }

        return $this->defaultSeoTitle();
    }

    private function hasRepeatedSeoPhrase(string $title): bool
    {
        return preg_match_all('/офисные\s+кресла/iu', $title) > 1;
    }

    private function hasForbiddenSeoPhrase(string $title): bool
    {
        return preg_match('/где\s+выгодно\s+приобрести|заказать\s+офисные\s+кресла|офисные\s+кресла\s+магазин/iu', $title) === 1;
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
