<?php

namespace App\Http\Controllers;

use App\Models\BlogPost;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Services\CacheService;
use Illuminate\Support\Facades\Cache;

class HomeController extends Controller
{
    public function index()
    {
        $hits = Cache::remember('home.hits', 1800, fn() =>
            Product::hits()
                ->with(['brand:id,name,slug', 'category:id,name,slug,parent_id', 'category.parent:id,slug'])
                ->orderByDesc('views')->limit(6)->get()
        );

        $newProducts = Cache::remember('home.new', 1800, fn() =>
            Product::new()
                ->with(['brand:id,name,slug', 'category:id,name,slug,parent_id', 'category.parent:id,slug'])
                ->latest()->limit(8)->get()
        );

        // Versioned key prevents the old cached root-category collection from
        // surviving after the homepage switched to child categories.
        $categories = Cache::remember('home.categories.v2', 3600, fn() =>
            Category::active()
                ->whereHas('parent', fn ($query) => $query
                    ->active()
                    ->where('slug', 'ofisnye-kresla'))
                ->with([
                    'parent:id,slug',
                    'products' => fn ($query) => $query
                        ->active()
                        ->whereNotNull('main_image')
                        ->orderByDesc('views')
                        ->limit(1)
                        ->select(['id', 'category_id', 'name', 'main_image', 'main_image_alt']),
                ])
                ->withCount([
                    'products as active_products_count' => fn ($query) => $query->active(),
                ])
                ->ordered()
                ->limit(6)
                ->get()
        );

        $brands = Cache::remember('home.brands', 86400, fn() =>
            Brand::active()->ordered()->whereHas('products')->limit(12)->get()
        );

        $blogPosts = Cache::remember('home.blog', 3600, fn() =>
            BlogPost::where('is_active', true)->latest('published_at')->limit(3)->get()
        );

        $totalProducts = Cache::remember('home.total', 3600, fn() =>
            Product::active()->count()
        );

        if ($hits->isEmpty()) {
            $hits = Cache::remember('home.hits.fallback', 1800, fn() =>
                Product::active()
                    ->inStock()
                    ->with(['brand:id,name,slug', 'category:id,name,slug,parent_id', 'category.parent:id,slug'])
                    ->whereNotNull('main_image')
                    ->orderByDesc('views')
                    ->limit(6)
                    ->get()
            );
        }

        $ogImage = asset('images/home-office-chair.webp');

        return view('pages.home', compact(
            'hits', 'newProducts', 'categories', 'brands',
            'blogPosts', 'totalProducts', 'ogImage'
        ));
    }
}
