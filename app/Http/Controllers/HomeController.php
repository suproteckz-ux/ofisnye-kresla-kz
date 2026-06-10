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
                ->orderByDesc('views')->limit(8)->get()
        );

        $newProducts = Cache::remember('home.new', 1800, fn() =>
            Product::new()
                ->with(['brand:id,name,slug', 'category:id,name,slug,parent_id', 'category.parent:id,slug'])
                ->latest()->limit(8)->get()
        );

        $categories = Cache::remember('home.categories', 3600, fn() =>
            Category::active()->root()->ordered()->withCount('products')->limit(6)->get()
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

        // Hero: хит с фото
        $heroProduct = $hits->first(fn($p) => !empty($p->main_image));

        return view('pages.home', compact(
            'hits', 'newProducts', 'categories', 'brands',
            'blogPosts', 'totalProducts', 'heroProduct'
        ));
    }
}
