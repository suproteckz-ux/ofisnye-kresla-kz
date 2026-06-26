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
        {--kaspi-url= : Use a direct Kaspi product URL and skip public product page resolver}
        {--url= : Alias for --kaspi-url}
        {--limit=10 : Maximum products to process}
        {--dry-run : Resolve and inspect without writing files or database changes}
        {--debug : Print step-by-step resolver/extractor diagnostics}
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
        $debug = (bool) $this->option('debug');
        $forceResolve = (bool) $this->option('force-resolve');
        $onlyMissing = (bool) $this->option('only-missing');
        $delayMs = max(0, (int) $this->option('delay-ms'));
        $limit = max(1, (int) $this->option('limit'));
        $directKaspiUrl = trim((string) ($this->option('kaspi-url') ?: $this->option('url')));

        if ($directKaspiUrl !== '' && ! $this->option('sku') && ! $this->option('id')) {
            $this->error('Use --kaspi-url only with --sku or --id so one Kaspi URL is not applied to multiple products.');
            return self::FAILURE;
        }

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
                if ($directKaspiUrl !== '') {
                    $kaspiUrl = $directKaspiUrl;
                    $log->forceFill([
                        'resolved_kaspi_url' => $kaspiUrl,
                        'kaspi_button_found' => false,
                        'kaspi_widget_opened' => false,
                    ]);
                    $this->saveLog($log);

                    if ($debug) {
                        $this->debugBlock('Direct Kaspi URL mode', [
                            'product_id' => $product->id,
                            'sku' => $product->sku,
                            'kaspi_url' => $kaspiUrl,
                            'resolver_skipped' => true,
                            'dry_run' => $dryRun,
                        ]);
                    }
                } else {
                    $kaspiUrl = $resolver->resolve($product, $log, $forceResolve, ! $dryRun, $debug);

                    if ($debug) {
                        $this->debugBlock('Kaspi resolver', $resolver->lastData());
                    }
                }

                if (! $kaspiUrl) {
                    $this->error("#{$product->id} {$product->sku}: Kaspi URL not resolved");
                    $log->forceFill([
                        'status' => $log->status ?: 'kaspi_url_not_resolved',
                        'finished_at' => now(),
                    ]);
                    $this->saveLog($log);
                    continue;
                }

                $extracted = $extractor->extract($kaspiUrl, (int) $product->id, (string) $product->sku, $debug);
                $photoUrls = $extracted['photo_urls'] ?? [];

                $log->forceFill([
                    'kaspi_page_loaded' => (bool) ($extracted['kaspi_page_loaded'] ?? false),
                    'photos_found' => count($photoUrls),
                ]);
                $this->saveLog($log);

                if ($debug) {
                    $this->debugBlock('Kaspi photo extractor', [
                        'product_id' => $product->id,
                        'sku' => $product->sku,
                        'kaspi_url' => $kaspiUrl,
                        'kaspi_page_loaded' => (bool) ($extracted['kaspi_page_loaded'] ?? false),
                        'photos_found' => count($photoUrls),
                        'photo_urls' => $photoUrls,
                        'dry_run' => $dryRun,
                        'error_message' => $extracted['error'] ?? $extracted['error_message'] ?? null,
                        'artifact_paths' => $extracted['artifact_paths'] ?? [],
                    ]);
                }

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
                $actualPhotosSaved = $dryRun ? 0 : $stats['photos_saved'];
                $mainImageChanged = $dryRun ? false : $stats['main_image_changed'];
                $status = $stats['photos_saved'] > 0
                    ? ($dryRun ? 'dry_run_ok' : 'appended')
                    : ($stats['duplicates_skipped'] > 0 ? 'duplicate_skipped' : 'no_photos_saved');

                $log->forceFill([
                    'photos_downloaded' => $stats['photos_downloaded'],
                    'photos_saved' => $actualPhotosSaved,
                    'duplicates_skipped' => $stats['duplicates_skipped'],
                    'main_image_changed' => $mainImageChanged,
                    'status' => $status,
                    'error_message' => implode("\n", array_slice($stats['errors'], 0, 10)) ?: null,
                    'finished_at' => now(),
                ]);
                $this->saveLog($log);

                if ($debug) {
                    $this->debugBlock('Kaspi import result', [
                        'product_id' => $product->id,
                        'sku' => $product->sku,
                        'photos_found' => count($photoUrls),
                        'photos_downloaded' => $stats['photos_downloaded'],
                        'photos_saved' => $actualPhotosSaved,
                        'photos_would_save' => $dryRun ? $stats['photos_saved'] : null,
                        'duplicates_skipped' => $stats['duplicates_skipped'],
                        'main_image_changed' => $mainImageChanged,
                        'dry_run' => $dryRun,
                        'error_message' => implode("\n", array_slice($stats['errors'], 0, 10)) ?: null,
                    ]);
                }

                $this->line(sprintf(
                    '#%d %s: found %d, downloaded %d, saved %d, duplicates %d%s',
                    $product->id,
                    $product->sku,
                    count($photoUrls),
                    $stats['photos_downloaded'],
                    $actualPhotosSaved,
                    $stats['duplicates_skipped'],
                    $mainImageChanged ? ', main image set' : ''
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

    private function debugBlock(string $title, array $data): void
    {
        $this->newLine();
        $this->line("<comment>{$title}</comment>");

        foreach ($data as $key => $value) {
            if ($value === null) {
                continue;
            }

            if (is_bool($value)) {
                $value = $value ? 'true' : 'false';
            }

            if (is_array($value)) {
                $this->line("  {$key}:");
                if ($value === []) {
                    $this->line('    []');
                    continue;
                }

                foreach ($value as $itemKey => $itemValue) {
                    if (is_array($itemValue)) {
                        $itemValue = json_encode($itemValue, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                    } elseif (is_bool($itemValue)) {
                        $itemValue = $itemValue ? 'true' : 'false';
                    }
                    $this->line("    {$itemKey}: {$itemValue}");
                }
                continue;
            }

            $this->line("  {$key}: {$value}");
        }
    }
}
