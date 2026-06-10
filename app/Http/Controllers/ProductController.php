<?php

namespace App\Http\Controllers;

use App\Models\Product;

class ProductController extends Controller
{

    /**
     * /product/{slug} → 301 → чистый SEO URL товара
     */
    public function redirectToSeoUrl(string $slug)
    {
        // Ищем товар — без active() чтобы не терять неактивные при редиректе
        $product = Product::where('slug', $slug)
            ->with(['category', 'category.parent'])
            ->first();

        if (! $product) {
            abort(404);
        }

        $targetUrl = $product->url;

        // Защита от бесконечного редиректа: если url вернул /product/{slug}
        // (категория не загружена), не редиректим на тот же URL
        if (str_contains($targetUrl, '/product/' . $slug)) {
            abort(404);
        }

        return redirect($targetUrl, 301);
    }

    /**
     * /{parent}/{child}/{product} — товар в подкатегории
     */
    public function showByCategoryPath(string $parent, string $child, string $productSlug)
    {
        // Валидируем путь: parent — корневая, child — подкат., product — товар
        $parentCat = \App\Models\Category::active()
            ->where('slug', $parent)
            ->whereNull('parent_id')
            ->first();

        if (! $parentCat) {
            abort(404);
        }

        $childCat = \App\Models\Category::active()
            ->where('slug', $child)
            ->where('parent_id', $parentCat->id)
            ->first();

        if (! $childCat) {
            abort(404);
        }

        $product = Product::active()
            ->where('slug', $productSlug)
            ->where('category_id', $childCat->id)
            ->with(['brand', 'category.parent', 'images'])
            ->first();

        if (! $product) {
            abort(404);
        }

        return $this->renderProduct($product);
    }

    /**
     * Общий рендер страницы товара
     */
    private function renderProduct(Product $product)
    {
        $product->incrementViews();

        $similar = Product::active()
            ->where('category_id', $product->category_id)
            ->where('id', '!=', $product->id)
            ->with([
                'brand:id,name,slug',
                'category:id,name,slug,parent_id',
                'category.parent:id,name,slug',
            ])
            ->inRandomOrder()
            ->limit(4)
            ->get();

        $breadcrumbs = [['name' => 'Каталог', 'url' => route('catalog')]];
        if ($product->category) {
            if ($product->category->parent) {
                $breadcrumbs[] = [
                    'name' => $product->category->parent->name,
                    'url'  => url('/' . $product->category->parent->slug),
                ];
            }
            $breadcrumbs[] = [
                'name' => $product->category->name,
                'url'  => $product->category->url,
            ];
        }
        $breadcrumbs[] = ['name' => $product->name];

        return view('pages.product', compact('product', 'similar', 'breadcrumbs'));
    }


}
