<?php

namespace App\Services\MarketRadar;

use RuntimeException;
use SimpleXMLElement;

class MarketRadarFeedParser
{
    /**
     * @return array<string, MarketRadarOffer>
     */
    public function parse(string $xml): array
    {
        libxml_use_internal_errors(true);
        $feed = simplexml_load_string($xml);
        $errors = libxml_get_errors();
        libxml_clear_errors();

        if (! $feed instanceof SimpleXMLElement) {
            $message = $errors[0]->message ?? 'Invalid XML';
            throw new RuntimeException(trim($message));
        }

        $offers = [];
        foreach ($feed->xpath('//offer') ?: [] as $offerNode) {
            $offerId = $this->normalizeSku((string) ($offerNode['id'] ?? ''));
            if ($offerId === '') {
                continue;
            }

            $pictures = [];
            foreach ($offerNode->picture ?? [] as $picture) {
                $url = trim((string) $picture);
                if ($url !== '' && filter_var($url, FILTER_VALIDATE_URL)) {
                    $pictures[$url] = $url;
                }
            }

            $offers[$offerId] = new MarketRadarOffer(
                offerId: $offerId,
                vendorCode: $this->nullableSku((string) ($offerNode->vendorCode ?? '')),
                price: $this->price((string) ($offerNode->price ?? '')),
                available: $this->available((string) ($offerNode['available'] ?? '')),
                quantityInStock: $this->quantity((string) ($offerNode->quantity_in_stock ?? '')),
                pictures: array_values($pictures),
                name: trim((string) ($offerNode->name ?? '')) ?: null,
            );
        }

        return $offers;
    }

    public function normalizeSku(string $sku): string
    {
        $sku = preg_replace('/\s+/u', ' ', $sku) ?? $sku;

        return trim($sku);
    }

    private function nullableSku(string $sku): ?string
    {
        $sku = $this->normalizeSku($sku);

        return $sku !== '' ? $sku : null;
    }

    private function price(string $value): ?float
    {
        $value = trim(str_replace(',', '.', $value));
        if ($value === '' || ! is_numeric($value)) {
            return null;
        }

        $price = (float) $value;

        return $price > 0 ? $price : null;
    }

    private function quantity(string $value): ?int
    {
        $value = trim($value);
        if ($value === '' || ! is_numeric($value)) {
            return null;
        }

        return max(0, (int) $value);
    }

    private function available(string $value): bool
    {
        return in_array(strtolower(trim($value)), ['1', 'true', 'yes', 'available'], true);
    }
}
