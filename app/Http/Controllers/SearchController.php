<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;

class SearchController extends Controller
{
    private const MIN_QUERY_LENGTH = 2;
    private const MAX_QUERY_LENGTH = 100;
    private const PER_PAGE         = 24;

    public function index(Request $request)
    {
        $query    = mb_substr(trim($request->get('q', '')), 0, self::MAX_QUERY_LENGTH);
        $products = collect();

        if (mb_strlen($query) >= self::MIN_QUERY_LENGTH) {
            $products = $this->search($query);
        }

        return response()
            ->view('pages.search', compact('query', 'products'))
            ->header('X-Robots-Tag', 'noindex, follow');
    }

    private function search(string $query): \Illuminate\Contracts\Pagination\LengthAwarePaginator
    {
        return Product::active()
            ->with(['brand:id,name,slug', 'category:id,name,slug,parent_id', 'category.parent:id,slug'])
            ->select([
                'id', 'name', 'slug', 'sku', 'price', 'old_price',
                'main_image', 'main_image_webp', 'main_image_alt',
                'in_stock', 'is_hit', 'is_new', 'brand_id', 'category_id',
            ])
            ->where(function ($q) use ($query) {
                $q->where('name',              'like', "%{$query}%")
                  ->orWhere('sku',             'like', "%{$query}%")
                  ->orWhere('short_description','like', "%{$query}%")
                  ->orWhere('description',     'like', "%{$query}%")
                  ->orWhereHas('brand', fn($b) =>
                      $b->where('name', 'like', "%{$query}%")
                  );
            })
            ->orderByDesc('is_hit')
            ->orderByDesc('views')
            ->paginate(self::PER_PAGE)
            ->withQueryString();
    }
}
