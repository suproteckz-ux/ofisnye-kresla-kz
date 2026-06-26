<?php

namespace App\Services\MarketRadar;

class MarketRadarOffer
{
    public function __construct(
        public readonly string $offerId,
        public readonly ?string $vendorCode,
        public readonly ?float $price,
        public readonly bool $available,
        public readonly ?int $quantityInStock,
        public readonly array $pictures,
        public readonly ?string $name,
    ) {}

    public function skuKeys(): array
    {
        return array_values(array_unique(array_filter([
            $this->offerId,
            $this->vendorCode,
        ], fn ($value): bool => trim((string) $value) !== '')));
    }
}
