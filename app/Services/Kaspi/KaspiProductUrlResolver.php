<?php

namespace App\Services\Kaspi;

use App\Models\KaspiPhotoImportLog;
use App\Models\Product;

class KaspiProductUrlResolver
{
    private array $lastData = [];

    public function __construct(private readonly KaspiBrowser $browser) {}

    public function resolve(Product $product, KaspiPhotoImportLog $log, bool $forceResolve = false, bool $persist = true, bool $debug = false): ?string
    {
        $this->lastData = [];

        if (! $forceResolve && ! empty($product->resolved_kaspi_url)) {
            $this->lastData = [
                'product_id' => $product->id,
                'sku' => $product->sku,
                'product_url' => $product->url,
                'page_loaded' => true,
                'kaspi_button_found' => true,
                'kaspi_widget_opened' => true,
                'resolved_kaspi_url' => $product->resolved_kaspi_url,
                'cached' => true,
            ];

            $log->forceFill([
                'kaspi_button_found' => true,
                'kaspi_widget_opened' => true,
                'resolved_kaspi_url' => $product->resolved_kaspi_url,
            ]);
            $this->saveLog($log);

            return $product->resolved_kaspi_url;
        }

        $product->loadMissing(['category.parent']);
        $productUrl = $product->url;
        $log->forceFill(['product_url' => $productUrl]);
        $this->saveLog($log);

        $result = $this->browser->run('kaspi-resolve-url.mjs', [
            $productUrl,
            (string) $product->id,
            (string) $product->sku,
            $debug ? '1' : '0',
        ], 60);
        $data = $result->data;
        $this->lastData = $data;

        $log->forceFill([
            'kaspi_button_found' => (bool) ($data['kaspi_button_found'] ?? false),
            'kaspi_widget_opened' => (bool) ($data['kaspi_widget_opened'] ?? false),
            'resolved_kaspi_url' => $data['resolved_kaspi_url'] ?? null,
            'error_message' => $data['error_message'] ?? $data['message'] ?? null,
        ]);
        $this->saveLog($log);

        $url = $data['resolved_kaspi_url'] ?? null;
        if (! $url) {
            $log->forceFill(['status' => $data['error'] ?? 'kaspi_url_not_resolved']);
            $this->saveLog($log);
            return null;
        }

        if ($persist) {
            $product->forceFill(['resolved_kaspi_url' => $url])->save();
        }

        return $url;
    }

    public function lastData(): array
    {
        return $this->lastData;
    }

    private function saveLog(KaspiPhotoImportLog $log): void
    {
        if ($log->exists) {
            $log->save();
        }
    }
}
