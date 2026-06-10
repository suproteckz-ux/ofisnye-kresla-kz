<?php

namespace App\Providers;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Observers\BrandObserver;
use App\Observers\CategoryObserver;
use App\Observers\ProductObserver;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // ── Observers ─────────────────────────────────────────────
        Product::observe(ProductObserver::class);
        Category::observe(CategoryObserver::class);
        Brand::observe(BrandObserver::class);

        // ── Пагинация через Tailwind CSS ──────────────────────────
        Paginator::useTailwind();

        // ── Rate limiters ─────────────────────────────────────────
        // Поиск: 30 запросов в минуту с одного IP
        RateLimiter::for('search', function (Request $request) {
            return Limit::perMinute(30)->by($request->ip());
        });

        // Заявки: 10 в час с одного IP
        RateLimiter::for('leads', function (Request $request) {
            return Limit::perHour(10)->by($request->ip());
        });
    }
}
