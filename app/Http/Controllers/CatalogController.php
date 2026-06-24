<?php

namespace App\Http\Controllers;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\SeoFilter;
use App\Models\SeoPage;
use Illuminate\Http\Request;

class CatalogController extends Controller
{
    private const PER_PAGE = 24;

    // Атрибуты кресел для фильтрации
    private const FILTERABLE_ATTRIBUTES = [
        'Основной обивочный материал' => ['label' => 'Материал', 'param' => 'material'],
        'Тип кресла'                  => ['label' => 'Тип',      'param' => 'type'],
        'Цвет'                        => ['label' => 'Цвет',     'param' => 'color'],
        'Синхромеханизм'              => ['label' => 'Механизм', 'param' => 'mechanism'],
        'Максимально допустимая нагрузка' => ['label' => 'Нагрузка', 'param' => 'load'],
    ];

    public function index(Request $request)
    {
        // /catalog показывает все товары напрямую — без лишнего клика
        $brands = Brand::active()
            ->whereHas('products', fn($q) => $q->active())
            ->ordered()->get();

        $query = Product::active()
            ->with(['brand:id,name,slug', 'category:id,name,slug,parent_id', 'category.parent:id,slug']);

        $hasFilters = false;

        if ($request->filled('brand')) {
            $query->where('brand_id', (int) $request->brand);
            $hasFilters = true;
        }
        if ($request->filled('in_stock')) {
            $query->where('in_stock', true);
            $hasFilters = true;
        }
        if ($request->filled('price_min')) {
            $query->where('price', '>=', (float) $request->price_min);
            $hasFilters = true;
        }
        if ($request->filled('price_max')) {
            $query->where('price', '<=', (float) $request->price_max);
            $hasFilters = true;
        }

        $activeAttributeFilters = [];
        foreach (self::FILTERABLE_ATTRIBUTES as $attrName => $config) {
            $paramValue = $request->get($config['param']);
            if (!empty($paramValue)) {
                $query->whereRaw(
                    "JSON_EXTRACT(attributes, ?) = ?",
                    ['$."' . $attrName . '"', $paramValue]
                );
                $activeAttributeFilters[$config['param']] = $paramValue;
                $hasFilters = true;
            }
        }

        $sort    = $request->get('sort', 'default');
        $hasSort = $sort !== 'default';
        match ($sort) {
            'price_asc'  => $query->orderBy('price'),
            'price_desc' => $query->orderByDesc('price'),
            'new'        => $query->latest(),
            'popular'    => $query->orderByDesc('views'),
            default      => $query->orderBy('sort_order')->orderByDesc('is_hit'),
        };

        $products = $query->paginate(self::PER_PAGE)->withQueryString();

        $priceRange = Product::active()
            ->selectRaw('MIN(price) as min_price, MAX(price) as max_price')
            ->first();

        $allCategoryIds = Category::active()->pluck('id')->toArray();
        $availableAttributes = $this->getAvailableAttributes($allCategoryIds);

        // Плитки дочерних категорий над товарами (дети «Офисные кресла», без самой родительской)
        $rootCat      = Category::where('slug', 'ofisnye-kresla')->first();
        $subCategories = $rootCat
            ? Category::active()
                ->where('parent_id', $rootCat->id)
                ->ordered()
                ->withCount('products')
                ->get()
            : collect();

        $noindex   = $hasFilters || $hasSort;
        $canonical = url('/catalog');

        $breadcrumbs = [['name' => 'Каталог', 'url' => route('catalog')]];

        return view('pages.catalog', compact(
            'products', 'brands', 'priceRange', 'sort', 'canonical', 'noindex',
            'breadcrumbs', 'availableAttributes', 'activeAttributeFilters', 'subCategories'
        ));
    }




    public static function filterableAttributes(): array
    {
        return self::FILTERABLE_ATTRIBUTES;
    }

    // ══════════════════════════════════════════════════════════════
    // /{slug} — один сегмент: категория ИЛИ SEO-страница
    // ══════════════════════════════════════════════════════════════

    public function oneSegment(Request $request, string $slug): \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse|\Illuminate\Contracts\View\View
    {
        // 1. Попробуем категорию верхнего уровня
        $category = Category::active()
            ->where('slug', $slug)
            ->whereNull('parent_id')
            ->with('children')
            ->first();

        if ($category) {
            return $this->renderCategoryPage($request, $category);
        }

        // 2. Fallback: SEO-страница из БД
        $page = SeoPage::active()
            ->where('slug', $slug)
            ->with([
                'products' => fn($q) => $q->active()->with([
                    'brand',
                    'category:id,name,slug,parent_id',
                    'category.parent:id,slug',
                ]),
                'categories',
                'categories.parent:id,slug',
                'categories.products' => fn($q) => $q->active()
                    ->whereNotNull('main_image')
                    ->where('main_image', '!=', '')
                    ->select(['id', 'category_id', 'main_image', 'is_active', 'is_hit', 'sort_order']),
                'categories.children' => fn($q) => $q->active()
                    ->select(['id', 'parent_id', 'name', 'slug', 'image', 'meta_description', 'seo_text_top']),
                'categories.children.products' => fn($q) => $q->active()
                    ->whereNotNull('main_image')
                    ->where('main_image', '!=', '')
                    ->select(['id', 'category_id', 'main_image', 'is_active', 'is_hit', 'sort_order']),
            ])
            ->first();

        if ($page) {
            $breadcrumbs = [['name' => $page->seoH1()]];
            $relatedProducts = $page->relatedLandingProducts();
            $heroImage = $page->heroImagePath($relatedProducts);

            return view('pages.seo-page', compact('page', 'breadcrumbs', 'relatedProducts', 'heroImage'));
        }

        abort(404);
    }

    // ══════════════════════════════════════════════════════════════
    // /{parent}/{child} — два сегмента:
    //   1. Подкатегория (parent = корневая категория, child = подкат.)
    //   2. Товар в категории верхнего уровня (parent = категория, child = товар)
    //   3. SEO-фильтр (parent = category.slug, child = brand.slug)
    // ══════════════════════════════════════════════════════════════

    public function twoSegment(Request $request, string $parent, string $child): \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse|\Illuminate\Contracts\View\View
    {
        // 1. Подкатегория: parent — корневая категория, child — дочерняя
        $parentCat = Category::active()
            ->where('slug', $parent)
            ->whereNull('parent_id')
            ->with('children')
            ->first();

        if ($parentCat) {
            $childCat = Category::active()
                ->where('slug', $child)
                ->where('parent_id', $parentCat->id)
                ->with('children')
                ->first();

            if ($childCat) {
                $childCat->setRelation('parent', $parentCat);
                return $this->renderCategoryPage($request, $childCat, $parentCat);
            }

            // 2. Товар в категории верхнего уровня: /{category.slug}/{product.slug}
            $product = Product::active()
                ->where('slug', $child)
                ->where('category_id', $parentCat->id)
                ->with(['brand', 'category.parent', 'images'])
                ->first();

            if ($product) {
                return $this->serveProduct($request, $product);
            }
        }

        // 3. SEO-фильтр: /{categorySlug}/{brandSlug}
        $filter = \App\Models\SeoFilter::active()
            ->where('is_indexed', true)
            ->whereHas('category', fn($q) => $q->active()->where('slug', $parent))
            ->whereHas('brand',    fn($q) => $q->active()->where('slug', $child))
            ->with(['category:id,name,slug,parent_id', 'brand:id,name,slug'])
            ->first();

        if ($filter) {
            $products = Product::active()
                ->where('category_id', $filter->category_id)
                ->where('brand_id',    $filter->brand_id)
                ->with(['brand:id,name,slug', 'category:id,name,slug,parent_id', 'category.parent:id,slug'])
                ->orderByDesc('is_hit')
                ->paginate(24);

            $canonical   = url("/{$parent}/{$child}");
            $breadcrumbs = [
                ['name' => 'Каталог',                    'url' => route('catalog')],
                ['name' => $filter->category->name,     'url' => url("/{$parent}")],
                ['name' => $filter->brand->name],
            ];

            return view('pages.seo-page', compact('filter', 'products', 'canonical', 'breadcrumbs'));
        }

        abort(404);
    }

    // ══════════════════════════════════════════════════════════════
    // Вспомогательный: рендер страницы товара из контроллера каталога
    // ══════════════════════════════════════════════════════════════

    private function serveProduct(Request $request, Product $product): \Illuminate\Contracts\View\View
    {
        $product->incrementViews();

        $similar = Product::active()
            ->where('category_id', $product->category_id)
            ->where('id', '!=', $product->id)
            ->with(['brand:id,name,slug', 'category:id,name,slug,parent_id', 'category.parent:id,slug'])
            ->inRandomOrder()
            ->limit(4)
            ->get();

        $breadcrumbs = [['name' => 'Каталог', 'url' => route('catalog')]];
        if ($product->category) {
            $breadcrumbs[] = [
                'name' => $product->category->name,
                'url'  => $product->category->url,
            ];
        }
        $breadcrumbs[] = ['name' => $product->name];

        return view('pages.product', compact('product', 'similar', 'breadcrumbs'));
    }


    private function renderCategoryPage(Request $request, Category $category, ?Category $parent = null)
    {
        $categoryIds = collect([$category->id]);
        if ($category->children && $category->children->isNotEmpty()) {
            $categoryIds = $categoryIds->merge($category->children->pluck('id'));
        }

        $brands = Brand::active()
            ->whereHas('products', fn($q) => $q->active()->whereIn('category_id', $categoryIds))
            ->ordered()->get();

        $query = Product::active()
            ->whereIn('category_id', $categoryIds)
            ->with(['brand:id,name,slug', 'category:id,name,slug,parent_id', 'category.parent:id,slug']);

        $hasFilters = false;

        // Стандартные фильтры
        if ($request->filled('brand')) {
            $query->where('brand_id', (int)$request->brand);
            $hasFilters = true;
        }
        if ($request->filled('in_stock')) {
            $query->where('in_stock', true);
            $hasFilters = true;
        }
        if ($request->filled('price_min')) {
            $query->where('price', '>=', (float)$request->price_min);
            $hasFilters = true;
        }
        if ($request->filled('price_max')) {
            $query->where('price', '<=', (float)$request->price_max);
            $hasFilters = true;
        }

        // Фильтры по атрибутам кресел (JSON поле)
        $activeAttributeFilters = [];
        foreach (self::FILTERABLE_ATTRIBUTES as $attrName => $config) {
            $paramValue = $request->get($config['param']);
            if (!empty($paramValue)) {
                $query->whereRaw(
                    "JSON_EXTRACT(attributes, ?) = ?",
                    ['$."' . $attrName . '"', $paramValue]
                );
                $activeAttributeFilters[$config['param']] = $paramValue;
                $hasFilters = true;
            }
        }

        // Сортировка
        $sort    = $request->get('sort', 'default');
        $hasSort = $sort !== 'default';
        match ($sort) {
            'price_asc'  => $query->orderBy('price'),
            'price_desc' => $query->orderByDesc('price'),
            'new'        => $query->latest(),
            'popular'    => $query->orderByDesc('views'),
            default      => $query->orderBy('sort_order')->orderByDesc('is_hit'),
        };

        $products = $query->paginate(self::PER_PAGE)->withQueryString();

        $priceRange = Product::active()
            ->whereIn('category_id', $categoryIds)
            ->selectRaw('MIN(price) as min_price, MAX(price) as max_price')
            ->first();

        // Доступные значения атрибутов для фильтров
        $availableAttributes = $this->getAvailableAttributes($categoryIds->toArray());

        // SEO-фильтр
        $seoFilter = null;
        if ($request->filled('brand') && !$hasSort) {
            $brand = Brand::find((int)$request->brand);
            if ($brand) {
                $seoFilter = SeoFilter::active()
                    ->where('category_id', $category->id)
                    ->where('brand_id', $brand->id)->first();
            }
        }

        $baseUrl = $parent
            ? url("/{$parent->slug}/{$category->slug}")
            : url("/{$category->slug}");

        $currentPage = (int)$request->get('page', 1);

        if ($seoFilter) {
            $canonical = url("/{$category->slug}/{$seoFilter->brand->slug}");
            $noindex   = false;
        } elseif ($hasFilters || $hasSort) {
            $canonical = $baseUrl;
            $noindex   = true;
        } elseif ($currentPage > 1) {
            $canonical = $products->url($currentPage);
            $noindex   = false;
        } else {
            $canonical = $baseUrl;
            $noindex   = false;
        }

        $breadcrumbs = array_values(array_filter([
            ['name' => 'Каталог', 'url' => route('catalog')],
            $parent ? ['name' => $parent->name, 'url' => url("/{$parent->slug}")] : null,
            ['name' => $category->name, 'url' => $baseUrl],
        ]));

        return view('pages.category', compact(
            'category', 'parent', 'products', 'brands', 'priceRange',
            'seoFilter', 'sort', 'canonical', 'noindex', 'currentPage',
            'breadcrumbs', 'availableAttributes', 'activeAttributeFilters'
        ));
    }

    /** Собирает уникальные значения атрибутов по товарам в категории */
    private function getAvailableAttributes(array $categoryIds): array
    {
        $available = [];
        $attrNames = array_keys(self::FILTERABLE_ATTRIBUTES);

        $products = Product::active()
            ->whereIn('category_id', $categoryIds)
            ->whereNotNull('attributes')
            ->select('attributes')
            ->get();

        foreach ($products as $product) {
            // attributes_array использует безопасный геттер с обработкой двойного кодирования
            $attrs = $product->attributes_array;
            foreach ($attrNames as $attrName) {
                if (!empty($attrs[$attrName])) {
                    $config = self::FILTERABLE_ATTRIBUTES[$attrName];
                    $available[$config['param']]['label'] = $config['label'];
                    $available[$config['param']]['values'][$attrs[$attrName]] = $attrs[$attrName];
                }
            }
        }

        // Сортируем значения
        foreach ($available as &$attr) {
            ksort($attr['values']);
        }

        return $available;
    }


}
