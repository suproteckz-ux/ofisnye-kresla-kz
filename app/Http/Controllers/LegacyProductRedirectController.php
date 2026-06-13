<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Cache;

class LegacyProductRedirectController extends Controller
{
    public function root(string $legacyProduct): RedirectResponse
    {
        return $this->redirectByLegacySlug($legacyProduct);
    }

    public function nested(string $category, string $legacyProduct): RedirectResponse
    {
        return $this->redirectByLegacySlug($legacyProduct);
    }

    public function prom(): RedirectResponse
    {
        return redirect(url('/ofisnye-kresla/s-podgolovnikom'), 301);
    }

    private function redirectByLegacySlug(string $legacySlug): RedirectResponse
    {
        $normalizedSlug = $this->normalizeSku($legacySlug);

        $skuIndex = Cache::remember('legacy_product_sku_index', 3600, function (): array {
            return Product::active()
                ->whereNotNull('sku')
                ->pluck('id', 'sku')
                ->mapWithKeys(fn ($id, $sku) => [$this->normalizeSku((string) $sku) => (int) $id])
                ->filter(fn ($id, $sku) => $sku !== '')
                ->all();
        });

        uksort($skuIndex, fn (string $left, string $right) => mb_strlen($right) <=> mb_strlen($left));

        $productId = null;
        foreach ($skuIndex as $sku => $id) {
            if (str_starts_with($normalizedSlug, $sku)) {
                $productId = $id;
                break;
            }
        }

        if (! $productId) {
            abort(404);
        }

        $product = Product::active()
            ->with(['category', 'category.parent'])
            ->findOrFail($productId);

        if (! $product->category) {
            abort(404);
        }

        return redirect($product->url, 301);
    }

    private function normalizeSku(string $value): string
    {
        $value = mb_strtolower(rawurldecode($value));
        $value = strtr($value, [
            "\u{0430}" => 'a',
            "\u{0432}" => 'b',
            "\u{0435}" => 'e',
            "\u{043A}" => 'k',
            "\u{043C}" => 'm',
            "\u{043D}" => 'h',
            "\u{043E}" => 'o',
            "\u{0440}" => 'p',
            "\u{0441}" => 'c',
            "\u{0442}" => 't',
            "\u{0445}" => 'x',
        ]);

        return preg_replace('/[^a-z0-9]+/', '', $value) ?? '';
    }
}
