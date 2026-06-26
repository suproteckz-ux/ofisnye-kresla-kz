<?php

namespace App\Console\Commands;

use App\Models\MarketRadarSyncLog;
use App\Models\Product;
use App\Services\MarketRadar\MarketRadarFeedClient;
use App\Services\MarketRadar\MarketRadarMatcher;
use App\Services\MarketRadar\MarketRadarProductSync;
use Illuminate\Console\Command;

class MarketRadarSyncCommand extends Command
{
    protected $signature = 'marketradar:sync
        {--sku= : Sync one product SKU}
        {--limit=50 : Maximum products to process}
        {--dry-run : Show planned changes without writing product data}
        {--prices : Sync prices}
        {--stock : Sync stock and availability}
        {--photos : Sync photos}
        {--all : Sync prices, stock and photos}
        {--only-missing-photos : Load photos only for products with less than two photos}
        {--force-photos : Re-check XML photos and append only non-duplicates}
        {--no-photos : Do not sync photos}
        {--no-prices : Do not sync prices}
        {--no-stock : Do not sync stock}';

    protected $description = 'Sync product prices, stock and photos from MarketRadar XML by exact SKU.';

    public function handle(
        MarketRadarFeedClient $client,
        MarketRadarMatcher $matcher,
        MarketRadarProductSync $sync,
    ): int {
        $dryRun = (bool) $this->option('dry-run');
        $limit = max(1, (int) $this->option('limit'));
        $sku = trim((string) $this->option('sku'));

        $options = $this->syncOptions();

        try {
            $offers = $client->fetch();
        } catch (\Throwable $e) {
            MarketRadarSyncLog::create([
                'sku' => $sku ?: null,
                'status' => 'failed',
                'source_url' => config('services.marketradar.feed_url'),
                'error_message' => $e->getMessage(),
                'dry_run' => $dryRun,
                'started_at' => now(),
                'finished_at' => now(),
            ]);

            $this->error('MarketRadar feed failed: '.$e->getMessage());
            return self::FAILURE;
        }

        $products = Product::query()
            ->with(['images', 'marketradarSyncLogs'])
            ->whereNotNull('sku')
            ->where('sku', '!=', '')
            ->when($sku !== '', fn ($query) => $query->where('sku', $sku))
            ->orderBy('id')
            ->limit($limit)
            ->get();

        if ($products->isEmpty()) {
            $this->warn('No products found.');
            return self::SUCCESS;
        }

        $rows = [];
        foreach ($products as $product) {
            $match = $matcher->match($product, $offers);
            $offer = $match['offer'];
            $matchedBy = $match['matched_by'];

            if (! $offer) {
                MarketRadarSyncLog::create([
                    'product_id' => $product->id,
                    'sku' => $product->sku,
                    'matched_by' => 'not_found',
                    'status' => 'not_found',
                    'source_url' => config('services.marketradar.feed_url'),
                    'dry_run' => $dryRun,
                    'started_at' => now(),
                    'finished_at' => now(),
                ]);

                $rows[] = [$product->id, $product->sku, 'not_found', '', '', '', '', '', 0, 0, 'not_found'];
                continue;
            }

            $result = $sync->sync($product, $offer, $matchedBy, [
                ...$options,
                'dry_run' => $dryRun,
            ]);

            $rows[] = [
                $product->id,
                $product->sku,
                $matchedBy,
                $offer->offerId,
                $offer->vendorCode ?: '',
                $this->money($result['old_price']).' -> '.$this->money($result['new_price']),
                $result['old_quantity'].' -> '.($result['new_quantity'] ?? ''),
                ($result['old_available'] ? 'yes' : 'no').' -> '.($result['new_available'] ? 'yes' : 'no'),
                $result['photos_found'],
                $dryRun ? ($result['photos_would_save'] ?? 0) : $result['photos_saved'],
                $result['status'],
            ];
        }

        $this->table([
            'ID',
            'SKU',
            'matched_by',
            'offer_id',
            'vendorCode',
            'price',
            'quantity',
            'available',
            'photos',
            $dryRun ? 'would_save' : 'saved',
            'status',
        ], $rows);

        $this->info(($dryRun ? 'Dry-run complete. ' : 'MarketRadar sync complete. ').'Products checked: '.$products->count());

        return self::SUCCESS;
    }

    private function syncOptions(): array
    {
        $explicit = (bool) ($this->option('prices') || $this->option('stock') || $this->option('photos') || $this->option('all'));

        $prices = $explicit ? (bool) ($this->option('prices') || $this->option('all')) : true;
        $stock = $explicit ? (bool) ($this->option('stock') || $this->option('all')) : true;
        $photos = $explicit ? (bool) ($this->option('photos') || $this->option('all')) : true;

        if ($this->option('no-prices')) {
            $prices = false;
        }
        if ($this->option('no-stock')) {
            $stock = false;
        }
        if ($this->option('no-photos')) {
            $photos = false;
        }

        return [
            'prices' => $prices,
            'stock' => $stock,
            'photos' => $photos,
            'only_missing_photos' => (bool) $this->option('only-missing-photos'),
            'force_photos' => (bool) $this->option('force-photos'),
            'photo_threshold' => 2,
        ];
    }

    private function money(mixed $value): string
    {
        if ($value === null || $value === '') {
            return '';
        }

        return number_format((float) $value, 0, '.', ' ');
    }
}
