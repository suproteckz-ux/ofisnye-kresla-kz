<?php

namespace App\Services\Kaspi;

use Illuminate\Support\Facades\Http;

class KaspiPhotoExtractor
{
    private array $lastData = [];

    public function __construct(private readonly KaspiBrowser $browser) {}

    public function extract(string $kaspiUrl, ?int $productId = null, ?string $sku = null, bool $debug = false): array
    {
        $this->lastData = [];

        $result = $this->browser->run('kaspi-extract-photos.mjs', [
            $kaspiUrl,
            (string) ($productId ?: 0),
            (string) ($sku ?: 'unknown'),
            $debug ? '1' : '0',
        ], 60);
        $urls = $this->normalizeUrls($result->data['photo_urls'] ?? []);
        $this->lastData = array_merge($result->data, ['photo_urls' => $urls]);

        if ($urls !== []) {
            return array_merge($this->lastData, [
                'ok' => true,
                'kaspi_page_loaded' => true,
                'photo_urls' => $urls,
                'error' => null,
            ]);
        }

        $fallback = $this->extractViaHttp($kaspiUrl);
        $this->lastData = array_merge($this->lastData, [
            'photo_urls' => $fallback,
            'photos_found' => count($fallback),
        ]);

        return [
            'ok' => $fallback !== [],
            'kaspi_page_loaded' => $result->data['kaspi_page_loaded'] ?? false,
            'photo_urls' => $fallback,
            'error' => $fallback === [] ? ($result->error ?: 'photos_not_found') : null,
            'artifact_paths' => $result->data['artifact_paths'] ?? [],
        ];
    }

    public function lastData(): array
    {
        return $this->lastData;
    }

    private function extractViaHttp(string $kaspiUrl): array
    {
        try {
            $html = Http::timeout(30)
                ->withHeaders(['User-Agent' => 'Mozilla/5.0 Chrome/125.0'])
                ->get($kaspiUrl)
                ->body();
        } catch (\Throwable) {
            return [];
        }

        preg_match_all('~https?:\\\\?/\\\\?/[^"\'\s<>]+?\.(?:jpg|jpeg|png|webp)(?:\?[^"\'\s<>]*)?~iu', $html, $matches);

        return $this->normalizeUrls($matches[0] ?? []);
    }

    private function normalizeUrls(array $urls): array
    {
        $result = [];
        foreach ($urls as $url) {
            $url = str_replace(['\\/', '\u002F'], '/', (string) $url);
            $url = preg_replace('~/small/|/thumbnail/~i', '/large/', $url) ?? $url;
            $url = preg_replace('~_small(?=\.)|_\d+x\d+(?=\.)~i', '', $url) ?? $url;
            if (! filter_var($url, FILTER_VALIDATE_URL)) {
                continue;
            }
            $result[$url] = $url;
        }

        return array_values($result);
    }
}
