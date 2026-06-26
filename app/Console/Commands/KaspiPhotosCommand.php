<?php

namespace App\Console\Commands;

use App\Models\KaspiPhotoImportLog;
use App\Models\Product;
use App\Services\Kaspi\KaspiPhotoExtractor;
use App\Services\Kaspi\KaspiPhotoImporter;
use App\Services\Kaspi\KaspiProductUrlResolver;
use Illuminate\Console\Command;

class KaspiPhotosCommand extends Command
{
    protected $signature = 'kaspi:photos
        {--sku= : Import photos for one product SKU}
        {--id= : Import photos for one product ID}
        {--limit=10 : Maximum products to process}
        {--dry-run : Resolve and inspect without writing files or database changes}
        {--force : Run even if product already has gallery photos}
        {--force-resolve : Ignore cached resolved Kaspi URL}
        {--delay-ms=3000 : Delay between products}
        {--only-missing : Process only products without additional gallery photos}
        {--append-only : Keep existing product data and append gallery photos only}';

    protected $description = 'Append product gallery photos from Kaspi by opening the existing Kaspi widget.';

    public function handle(
        KaspiProductUrlResolver $resolver,
        KaspiPhotoExtractor $extractor,
        KaspiPhotoImporter $importer,
    ): int {
        $dryRun = (bool) $this->option('dry-run');
        $forceResolve = (bool) $this->option('force-resolve');
        $onlyMissing = (bool) $this->option('only-missing');
        $delayMs = max(0, (int) $this->option('delay-ms'));
        $limit = max(1, (int) $this->option('limit'));

        if ($limit > 10 && ! $this->option('sku') && ! $this->option('id')) {
            $this->warn('Limit capped to 10 products for safe Kaspi imports. Use SKU/ID for a targeted run.');
            $limit = 10;
        }

        $products = $this->products($limit, $onlyMissing);
        if ($products->isEmpty()) {
            $this->warn('No products found for Kaspi photo import.');
            return self::SUCCESS;
        }

        $this->info(($dryRun ? '[dry-run] ' : '') . 'Kaspi photo import: ' . $products->count() . ' product(s)');

        foreach ($products as $index => $product) {
            $log = new KaspiPhotoImportLog([
                'product_id' => $product->id,
                'sku' => $product->sku,
                'product_url' => $product->url,
                'status' => 'pending',
                'started_at' => now(),
            ]);
            if (! $dryRun) {
                $log->save();
            }

            try {
                $kaspiUrl = $resolver->resolve($product, $log, $forceResolve, ! $dryRun);
                if (! $kaspiUrl) {
                    $this->error("#{$product->id} {$product->sku}: Kaspi URL not resolved");
                    $log->forceFill([
                        'status' => $log->status ?: 'kaspi_url_not_resolved',
                        'finished_at' => now(),
                    ]);
                    $this->saveLog($log);
                    continue;
                }

                $extracted = $extractor->extract($kaspiUrl);
                $photoUrls = $extracted['photo_urls'] ?? [];

                $log->forceFill([
                    'kaspi_page_loaded' => (bool) ($extracted['kaspi_page_loaded'] ?? false),
                    'photos_found' => count($photoUrls),
                ]);
                $this->saveLog($log);

                if ($photoUrls === []) {
                    $this->warn("#{$product->id} {$product->sku}: no photos found");
                    $log->forceFill([
                        'status' => $extracted['error'] ?: 'photos_not_found',
                        'error_message' => $extracted['error'] ?? null,
                        'finished_at' => now(),
                    ]);
                    $this->saveLog($log);
                    continue;
                }

                $stats = $importer->import($product, $photoUrls, $dryRun);
                $status = $stats['photos_saved'] > 0
                    ? ($dryRun ? 'dry_run_ok' : 'appended')
                    : ($stats['duplicates_skipped'] > 0 ? 'duplicate_skipped' : 'no_photos_saved');

                $log->forceFill([
                    'photos_downloaded' => $stats['photos_downloaded'],
                    'photos_saved' => $stats['photos_saved'],
                    'duplicates_skipped' => $stats['duplicates_skipped'],
                    'main_image_changed' => $stats['main_image_changed'],
                    'status' => $status,
                    'error_message' => implode("\n", array_slice($stats['errors'], 0, 10)) ?: null,
                    'finished_at' => now(),
                ]);
                $this->saveLog($log);

                $this->line(sprintf(
                    '#%d %s: found %d, downloaded %d, saved %d, duplicates %d%s',
                    $product->id,
                    $product->sku,
                    count($photoUrls),
                    $stats['photos_downloaded'],
                    $stats['photos_saved'],
                    $stats['duplicates_skipped'],
                    $stats['main_image_changed'] ? ', main image set' : ''
                ));
            } catch (\Throwable $e) {
                $log->forceFill([
                    'status' => 'error',
                    'error_message' => $e->getMessage(),
                    'finished_at' => now(),
                ]);
                $this->saveLog($log);

                $this->error("#{$product->id} {$product->sku}: {$e->getMessage()}");
            }

            if ($delayMs > 0 && $index < $products->count() - 1) {
                usleep($delayMs * 1000);
            }
        }

        return self::SUCCESS;
    }

    private function products(int $limit, bool $onlyMissing)
    {
        $query = Product::query()
            ->with(['category.parent', 'images'])
            ->whereNotNull('sku')
            ->where('sku', '!=', '')
            ->orderByDesc('updated_at');

        if ($this->option('id')) {
            $query->whereKey((int) $this->option('id'));
        }

        if ($this->option('sku')) {
            $query->where('sku', (string) $this->option('sku'));
        }

        if ($onlyMissing) {
            $query->whereDoesntHave('images');
        }

        return $query->limit($limit)->get();
    }

    private function saveLog(KaspiPhotoImportLog $log): void
    {
        if ($log->exists) {
            $log->save();
        }
    }
}
