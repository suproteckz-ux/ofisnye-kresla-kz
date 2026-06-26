<?php

namespace App\Services\MarketRadar;

use App\Models\MarketRadarSyncLog;
use App\Models\Product;
use Throwable;

class MarketRadarProductSync
{
    public function __construct(private readonly MarketRadarImageImporter $imageImporter) {}

    public function sync(Product $product, MarketRadarOffer $offer, string $matchedBy, array $options = []): array
    {
        $dryRun = (bool) ($options['dry_run'] ?? false);
        $syncPrices = (bool) ($options['prices'] ?? true);
        $syncStock = (bool) ($options['stock'] ?? true);
        $syncPhotos = (bool) ($options['photos'] ?? true);
        $forcePhotos = (bool) ($options['force_photos'] ?? false);
        $onlyMissingPhotos = (bool) ($options['only_missing_photos'] ?? false);
        $defaultPhotoThreshold = (int) ($options['photo_threshold'] ?? 2);

        $product->loadMissing('images');

        $startedAt = now();
        $oldPrice = (float) $product->price;
        $oldQuantity = (int) $product->quantity;
        $oldAvailable = (bool) $product->in_stock;

        $log = new MarketRadarSyncLog([
            'product_id' => $product->id,
            'sku' => $product->sku,
            'offer_id' => $offer->offerId,
            'vendor_code' => $offer->vendorCode,
            'matched_by' => $matchedBy,
            'status' => $dryRun ? 'dry_run' : 'matched',
            'old_price' => $oldPrice,
            'new_price' => $offer->price,
            'old_quantity' => $oldQuantity,
            'new_quantity' => $offer->quantityInStock,
            'old_available' => $oldAvailable,
            'new_available' => $this->availableFromOffer($offer),
            'photos_found' => count($offer->pictures),
            'source_url' => config('services.marketradar.feed_url'),
            'dry_run' => $dryRun,
            'started_at' => $startedAt,
        ]);

        if (! $dryRun) {
            $log->save();
        }

        $result = [
            'product_id' => $product->id,
            'sku' => $product->sku,
            'offer_id' => $offer->offerId,
            'vendor_code' => $offer->vendorCode,
            'matched_by' => $matchedBy,
            'old_price' => $oldPrice,
            'new_price' => $offer->price,
            'old_quantity' => $oldQuantity,
            'new_quantity' => $offer->quantityInStock,
            'old_available' => $oldAvailable,
            'new_available' => $this->availableFromOffer($offer),
            'price_changed' => false,
            'stock_changed' => false,
            'photos_found' => count($offer->pictures),
            'photos_saved' => 0,
            'duplicates_skipped' => 0,
            'status' => $dryRun ? 'dry_run' : 'matched',
            'errors' => [],
        ];

        try {
            $update = [];

            if ($syncPrices) {
                if ($offer->price !== null && $offer->price > 0) {
                    if ((float) $product->price !== (float) $offer->price) {
                        $update['price'] = $offer->price;
                        $result['price_changed'] = true;
                    }
                } else {
                    $result['errors'][] = 'invalid_price';
                }
            }

            if ($syncStock) {
                if ($offer->quantityInStock !== null) {
                    $newAvailable = $this->availableFromOffer($offer);
                    if ((int) $product->quantity !== $offer->quantityInStock || (bool) $product->in_stock !== $newAvailable) {
                        $update['quantity'] = $offer->quantityInStock;
                        $update['in_stock'] = $newAvailable;
                        $result['stock_changed'] = true;
                    }
                } else {
                    $result['errors'][] = 'missing_quantity_in_stock';
                }
            }

            if ($update !== [] && ! $dryRun) {
                $product->forceFill($update)->save();
            }

            $shouldLoadPhotos = $syncPhotos
                && $offer->pictures !== []
                && (
                    $forcePhotos
                    || $product->images->count() < $defaultPhotoThreshold
                );

            if ($shouldLoadPhotos) {
                $photoStats = $this->imageImporter->import($product, $offer->pictures, $dryRun, $forcePhotos);
                $result['photos_saved'] = $dryRun ? 0 : $photoStats['photos_saved'];
                $result['photos_would_save'] = $dryRun ? $photoStats['photos_saved'] : null;
                $result['duplicates_skipped'] = $photoStats['duplicates_skipped'];
                $result['errors'] = [...$result['errors'], ...$photoStats['errors']];
            } elseif ($syncPhotos) {
                $result['status'] = 'skipped';
            }

            $status = $this->status($result, $dryRun);
            $result['status'] = $status;

            $log->forceFill([
                'status' => $status,
                'new_price' => $offer->price,
                'new_quantity' => $offer->quantityInStock,
                'new_available' => $this->availableFromOffer($offer),
                'photos_saved' => (int) $result['photos_saved'],
                'duplicates_skipped' => (int) $result['duplicates_skipped'],
                'error_message' => implode("\n", array_slice($result['errors'], 0, 10)) ?: null,
                'finished_at' => now(),
            ]);
            $this->saveLog($log);

            return $result;
        } catch (Throwable $e) {
            $result['status'] = 'failed';
            $result['errors'][] = $e->getMessage();

            $log->forceFill([
                'status' => 'failed',
                'error_message' => $e->getMessage(),
                'finished_at' => now(),
            ]);
            $this->saveLog($log);

            return $result;
        }
    }

    public function inspect(Product $product, ?MarketRadarOffer $offer, string $matchedBy, bool $dryRun = true): array
    {
        $log = MarketRadarSyncLog::create([
            'product_id' => $product->id,
            'sku' => $product->sku,
            'offer_id' => $offer?->offerId,
            'vendor_code' => $offer?->vendorCode,
            'matched_by' => $matchedBy,
            'status' => $offer ? 'dry_run' : 'not_found',
            'old_price' => (float) $product->price,
            'new_price' => $offer?->price,
            'old_quantity' => (int) $product->quantity,
            'new_quantity' => $offer?->quantityInStock,
            'old_available' => (bool) $product->in_stock,
            'new_available' => $offer ? $this->availableFromOffer($offer) : null,
            'photos_found' => $offer ? count($offer->pictures) : 0,
            'source_url' => config('services.marketradar.feed_url'),
            'dry_run' => $dryRun,
            'started_at' => now(),
            'finished_at' => now(),
        ]);

        return [
            'log_id' => $log->id,
            'product_id' => $product->id,
            'sku' => $product->sku,
            'matched_by' => $matchedBy,
            'offer_id' => $offer?->offerId,
            'vendor_code' => $offer?->vendorCode,
            'price' => $offer?->price,
            'quantity_in_stock' => $offer?->quantityInStock,
            'pictures_found' => $offer ? count($offer->pictures) : 0,
            'name' => $offer?->name,
            'status' => $offer ? 'found' : 'not_found',
        ];
    }

    private function availableFromOffer(MarketRadarOffer $offer): bool
    {
        if ($offer->quantityInStock !== null) {
            return $offer->quantityInStock > 0;
        }

        return $offer->available;
    }

    private function status(array $result, bool $dryRun): string
    {
        if ($dryRun) {
            return 'dry_run';
        }

        if (($result['photos_saved'] ?? 0) > 0) {
            return 'photos_updated';
        }

        if (($result['price_changed'] ?? false) && ($result['stock_changed'] ?? false)) {
            return 'matched';
        }

        if ($result['price_changed'] ?? false) {
            return 'price_updated';
        }

        if ($result['stock_changed'] ?? false) {
            return 'stock_updated';
        }

        if (($result['duplicates_skipped'] ?? 0) > 0) {
            return 'duplicate_skipped';
        }

        return 'skipped';
    }

    private function saveLog(MarketRadarSyncLog $log): void
    {
        if ($log->exists) {
            $log->save();
        }
    }
}
