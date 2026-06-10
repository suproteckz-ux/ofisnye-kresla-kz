<?php

use App\Http\Controllers\BlogController;
use App\Http\Controllers\BrandController;
use App\Http\Controllers\CatalogController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\LeadController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\SearchController;
use App\Http\Controllers\SeoPageController;
use App\Http\Controllers\SitemapController;
use Illuminate\Support\Facades\Route;

// ══════════════════════════════════════════════════════════════════
// БЛОК 1: Конкретные служебные маршруты (всегда первыми!)
// ══════════════════════════════════════════════════════════════════

Route::get('/', [HomeController::class, 'index'])->name('home');

Route::middleware('throttle:30,1')
    ->get('/search', [SearchController::class, 'index'])
    ->name('search');

Route::middleware('throttle:leads')
    ->post('/lead', [LeadController::class, 'store'])
    ->name('lead.store');

// Главный каталог (конкретный маршрут — над wildcards)
Route::get('/catalog', [CatalogController::class, 'index'])->name('catalog');

// Бренды
Route::get('/brand', [BrandController::class, 'index'])->name('brands');
Route::get('/brand/{brand}', [BrandController::class, 'show'])
    ->name('brand.show')
    ->where('brand', '[a-z0-9][a-z0-9\-]*');

// Блог
Route::get('/blog', [BlogController::class, 'index'])->name('blog');
Route::get('/blog/{post}', [BlogController::class, 'show'])
    ->name('blog.show')
    ->where('post', '[a-z0-9][a-z0-9\-]*');

// Health check
Route::get('/health', function () {
    try {
        \Illuminate\Support\Facades\DB::connection()->getPdo();
        return response()->json(['status' => 'ok', 'db' => 'connected', 'time' => now()->toIso8601String()]);
    } catch (\Throwable $e) {
        return response()->json(['status' => 'error', 'db' => 'failed'], 503);
    }
})->middleware('throttle:60,1');

// Sitemap
Route::get('/sitemap.xml',             [SitemapController::class, 'index']);
Route::get('/sitemap-products.xml',    [SitemapController::class, 'products']);
Route::get('/sitemap-categories.xml',  [SitemapController::class, 'categories']);
Route::get('/sitemap-brands.xml',      [SitemapController::class, 'brands']);
Route::get('/sitemap-blog.xml',        [SitemapController::class, 'blog']);
Route::get('/sitemap-seo-pages.xml',   [SitemapController::class, 'seoPages']);
Route::get('/sitemap-seo-filters.xml', [SitemapController::class, 'seoFilters']);

// Robots.txt
Route::get('/robots.txt', function () {
    $appUrl = rtrim(config('app.url'), '/');
    $lines = [
        'User-agent: *',
        '',
        'Disallow: /admin',
        'Disallow: /admin/',
        'Disallow: /lead',
        'Disallow: /health',
        'Disallow: /livewire/',
        'Disallow: /_ignition/',
        'Disallow: /search',
        'Disallow: /search?*',
        'Disallow: /*?brand=*',
        'Disallow: /*?price_min=*',
        'Disallow: /*?price_max=*',
        'Disallow: /*?in_stock=*',
        'Disallow: /*?sort=*',
        '',
        '# Разрешаем служебные',
        'Allow: /catalog$',
        'Allow: /brand/',
        'Allow: /blog/',
        'Allow: /sitemap',
        '',
        '# Чистые URL товаров и категорий разрешены по умолчанию',
        '',
        "Sitemap: {$appUrl}/sitemap.xml",
        '',
        'User-agent: Yandex',
        'Crawl-delay: 1',
        "Sitemap: {$appUrl}/sitemap.xml",
        '',
        'User-agent: Googlebot',
        'Crawl-delay: 0',
        "Sitemap: {$appUrl}/sitemap.xml",
    ];
    return response(implode("\n", $lines), 200, [
        'Content-Type'  => 'text/plain; charset=UTF-8',
        'Cache-Control' => 'public, max-age=86400',
    ]);
});

// ══════════════════════════════════════════════════════════════════
// БЛОК 2: 301-редиректы старых URL
// ══════════════════════════════════════════════════════════════════

// /catalog/{parent}/{child} → /{parent}/{child}
Route::get('/catalog/{parent}/{child}', function (string $parent, string $child) {
    return redirect(url("/{$parent}/{$child}"), 301);
})->where(['parent' => '[a-z0-9][a-z0-9\-]*', 'child' => '[a-z0-9][a-z0-9\-]*']);

// /catalog/{category} → /{category}
Route::get('/catalog/{category}', function (string $category) {
    return redirect(url("/{$category}"), 301);
})->where('category', '[a-z0-9][a-z0-9\-]*');

// /product/{slug} → 301 → чистый SEO URL товара
// Стоит ВЫШЕ wildcards /{parent}/{child}/{product} и /{slug}
Route::get('/product/{slug}', function (string $slug) {
    $product = \App\Models\Product::where('slug', $slug)
        ->with(['category', 'category.parent'])
        ->first();

    if (! $product) {
        abort(404);
    }

    return redirect($product->url, 301);
})->name('product.show')->where('slug', '[a-z0-9][a-z0-9\-]*');
// ══════════════════════════════════════════════════════════════════
// БЛОК 3: Чистые URL товаров — /{parent}/{child}/{product}
// ВАЖНО: ставим раньше /{parent}/{child}, т.к. три сегмента конкретнее
// ══════════════════════════════════════════════════════════════════

Route::get('/{parent}/{child}/{product}', [ProductController::class, 'showByCategoryPath'])
    ->name('product.clean')
    ->where([
        'parent'  => '[a-z0-9][a-z0-9\-]*',
        'child'   => '[a-z0-9][a-z0-9\-]*',
        'product' => '[a-z0-9][a-z0-9\-]*',
    ]);

// ══════════════════════════════════════════════════════════════════
// БЛОК 4: Два сегмента — /{parent}/{child}
// Обрабатывает: подкатегорию, товар в категории верхнего уровня, SEO-фильтр
// ══════════════════════════════════════════════════════════════════

Route::get('/{parent}/{child}', [CatalogController::class, 'twoSegment'])
    ->name('catalog.subcategory')
    ->where(['parent' => '[a-z0-9][a-z0-9\-]*', 'child' => '[a-z0-9][a-z0-9\-]*']);

// ══════════════════════════════════════════════════════════════════
// БЛОК 5: Один сегмент — /{slug}
// Обрабатывает: категорию верхнего уровня, SEO-страницу
// ВСЕГДА ПОСЛЕДНИМ из wildcards!
// ══════════════════════════════════════════════════════════════════

Route::get('/{slug}', [CatalogController::class, 'oneSegment'])
    ->name('catalog.category')
    ->where('slug', '[a-z0-9][a-z0-9\-]*');
