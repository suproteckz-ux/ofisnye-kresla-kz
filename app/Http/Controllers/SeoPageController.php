<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\SeoFilter;
use App\Models\SeoPage;

class SeoPageController extends Controller
{
    // ──────────────────────────────────────────────────────────────
    // /{slug} — SEO-страница из БД
    // ──────────────────────────────────────────────────────────────

    public function show(string $slug)
    {
        $page = SeoPage::active()
            ->where('slug', $slug)
            ->with([
                'products' => fn ($q) => $q->active()->with([
                    'brand',
                    'category:id,name,slug,parent_id',
                    'category.parent:id,slug',
                ]),
                'categories',
                'categories.parent:id,slug',
                'categories.products' => fn ($q) => $q->active()
                    ->whereNotNull('main_image')
                    ->where('main_image', '!=', '')
                    ->select(['id', 'category_id', 'main_image', 'is_active', 'is_hit', 'sort_order']),
                'categories.children' => fn ($q) => $q->active()
                    ->select(['id', 'parent_id', 'name', 'slug', 'image', 'meta_description', 'seo_text_top']),
                'categories.children.products' => fn ($q) => $q->active()
                    ->whereNotNull('main_image')
                    ->where('main_image', '!=', '')
                    ->select(['id', 'category_id', 'main_image', 'is_active', 'is_hit', 'sort_order']),
            ])
            ->first(); // first() → null, не исключение

        // SEO-страница не найдена → 404 (не 500)
        if (! $page) {
            abort(404);
        }

        $breadcrumbs = [
            ['name' => $page->seoH1()],
        ];

        $relatedProducts = $page->relatedLandingProducts();
        $heroImage = $page->heroImagePath($relatedProducts);

        return view('pages.seo-page', compact('page', 'breadcrumbs', 'relatedProducts', 'heroImage'));
    }

    // ──────────────────────────────────────────────────────────────
    // /{categorySlug}/{brandSlug} — SEO-фильтр
    // ──────────────────────────────────────────────────────────────

    public function filter(string $categorySlug, string $brandSlug)
    {
        // Ищем только вручную созданные, активные, индексируемые фильтры
        $filter = SeoFilter::active()
            ->where('is_indexed', true)
            ->whereHas('category', fn ($q) => $q->active()->where('slug', $categorySlug))
            ->whereHas('brand',    fn ($q) => $q->active()->where('slug', $brandSlug))
            ->with([
                'category:id,name,slug,parent_id',
                'brand:id,name,slug',
            ])
            ->first(); // first() → null, не firstOrFail()

        // SEO-фильтра нет → 404
        // НЕ редиректим на каталог, т.к. это может быть не наша страница
        if (! $filter) {
            abort(404);
        }

        // Товары этой категории + бренда
        $products = Product::active()
            ->where('category_id', $filter->category_id)
            ->where('brand_id',    $filter->brand_id)
            ->with([
                'brand:id,name,slug',
                'category:id,name,slug,parent_id',
                'category.parent:id,slug',
            ])
            ->orderByDesc('is_hit')
            ->paginate(24);

        $canonical = url("/{$categorySlug}/{$brandSlug}");

        $breadcrumbs = array_values(array_filter([
            ['name' => 'Каталог', 'url' => route('catalog')],
            ['name' => $filter->category->name, 'url' => url("/{$categorySlug}")],
            ['name' => $filter->brand->name],
        ]));

        return view('pages.seo-page', compact(
            'filter',
            'products',
            'canonical',
            'breadcrumbs'
        ));
    }
}
