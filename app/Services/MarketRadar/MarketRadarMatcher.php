<?php

namespace App\Services\MarketRadar;

use App\Models\Product;

class MarketRadarMatcher
{
    /**
     * @param array<string, MarketRadarOffer> $offers
     */
    public function match(Product $product, array $offers): array
    {
        $sku = $this->normalize((string) $product->sku);
        if ($sku === '') {
            return ['offer' => null, 'matched_by' => 'not_found'];
        }

        if (isset($offers[$sku])) {
            return ['offer' => $offers[$sku], 'matched_by' => 'offer_id'];
        }

        foreach ($offers as $offer) {
            if ($offer->vendorCode !== null && $this->normalize($offer->vendorCode) === $sku) {
                return ['offer' => $offer, 'matched_by' => 'vendor_code'];
            }
        }

        return ['offer' => null, 'matched_by' => 'not_found'];
    }

    private function normalize(string $sku): string
    {
        $sku = preg_replace('/\s+/u', ' ', $sku) ?? $sku;

        return trim($sku);
    }
}
