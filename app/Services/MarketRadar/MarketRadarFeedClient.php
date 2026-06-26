<?php

namespace App\Services\MarketRadar;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

class MarketRadarFeedClient
{
    public function __construct(private readonly MarketRadarFeedParser $parser) {}

    /**
     * @return array<string, MarketRadarOffer>
     */
    public function fetch(): array
    {
        $url = (string) config('services.marketradar.feed_url');
        if ($url === '') {
            throw new RuntimeException('MarketRadar feed URL is not configured.');
        }

        $response = Http::timeout(30)
            ->retry(2, 1000)
            ->accept('application/xml,text/xml,*/*')
            ->get($url);

        if (! $response->successful()) {
            throw new RuntimeException('MarketRadar feed request failed: HTTP '.$response->status());
        }

        $contentType = strtolower((string) $response->header('Content-Type'));
        $body = $response->body();

        if ($body === '' || (! str_contains($contentType, 'xml') && ! str_starts_with(ltrim($body), '<'))) {
            throw new RuntimeException('MarketRadar feed response is not XML.');
        }

        Storage::disk('local')->put('marketradar/feed.xml', $body);

        return $this->parser->parse($body);
    }
}
