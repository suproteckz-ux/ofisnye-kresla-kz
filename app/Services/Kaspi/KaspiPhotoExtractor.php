<?php

namespace App\Services\Kaspi;

use Illuminate\Support\Facades\Http;

class KaspiPhotoExtractor
{
    public function __construct(private readonly KaspiBrowser $browser) {}

    public function extract(string $kaspiUrl): array
    {
        $result = $this->browser->run('kaspi-extract-photos.mjs', [$kaspiUrl], 40);
        $urls = $this->normalizeUrls($result->data['photo_urls'] ?? []);

        if ($urls !== []) {
            return ['ok' => true, 'kaspi_page_loaded' => true, 'photo_urls' => $urls, 'error' => null];
        }

        $fallback = $this->extractViaHttp($kaspiUrl);

        return [
            'ok' => $fallback !== [],
            'kaspi_page_loaded' => $result->data['kaspi_page_loaded'] ?? false,
            'photo_urls' => $fallback,
            'error' => $fallback === [] ? ($result->error ?: 'photos_not_found') : null,
        ];
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
