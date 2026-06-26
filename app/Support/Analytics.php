<?php

namespace App\Support;

use App\Models\Product;
use Illuminate\Support\Collection;

class Analytics
{
    public static function productItem(Product $product, ?int $index = null): array
    {
        $product->loadMissing(['brand:id,name', 'category:id,name']);

        $item = [
            'item_id' => (string) ($product->sku ?: $product->id),
            'item_name' => (string) $product->name,
            'item_brand' => (string) ($product->brand?->name ?? ''),
            'item_category' => (string) ($product->category?->name ?? ''),
            'price' => (float) $product->price,
            'currency' => 'KZT',
            'quantity' => 1,
        ];

        if ($index !== null) {
            $item['index'] = $index;
        }

        return array_filter($item, fn ($value) => $value !== '');
    }

    public static function productItems(iterable $products): array
    {
        if ($products instanceof Collection) {
            $products = $products->values();
        }

        $items = [];
        foreach ($products as $index => $product) {
            if ($product instanceof Product) {
                $items[] = self::productItem($product, ((int) $index) + 1);
            }
        }

        return $items;
    }

    public static function selectItemPayload(Product $product, ?string $listId = null, ?string $listName = null, ?int $index = null): array
    {
        return [
            'event' => 'select_item',
            'ecommerce' => array_filter([
                'item_list_id' => $listId,
                'item_list_name' => $listName,
                'items' => [self::productItem($product, $index)],
            ], fn ($value) => $value !== null && $value !== ''),
        ];
    }
}
