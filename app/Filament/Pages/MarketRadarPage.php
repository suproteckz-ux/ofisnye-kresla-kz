<?php

namespace App\Filament\Pages;

use App\Models\MarketRadarSyncLog;
use App\Models\Product;
use App\Services\MarketRadar\MarketRadarFeedClient;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Artisan;
use Throwable;

class MarketRadarPage extends Page
{
    protected static ?string $slug = 'marketradar';

    protected string $view = 'filament.pages.marketradar';

    public string $sku = '1361A';

    public int $limit = 20;

    public ?array $feedInfo = null;

    public string $commandOutput = '';

    public static function getNavigationIcon(): string
    {
        return 'heroicon-o-signal';
    }

    public static function getNavigationGroup(): ?string
    {
        return 'Импорт';
    }

    public static function getNavigationLabel(): string
    {
        return 'MarketRadar';
    }

    public static function getNavigationSort(): int
    {
        return 1;
    }

    public function getTitle(): string
    {
        return 'MarketRadar';
    }

    public function feedUrl(): string
    {
        return (string) config('services.marketradar.feed_url');
    }

    public function lastLog(): ?MarketRadarSyncLog
    {
        return MarketRadarSyncLog::query()->latest()->first();
    }

    public function recentLogs(): Collection
    {
        return MarketRadarSyncLog::query()->latest()->limit(20)->get();
    }

    public function checkFeed(): void
    {
        try {
            $offers = app(MarketRadarFeedClient::class)->fetch();
            $offerIds = array_fill_keys(array_keys($offers), true);
            $vendorCodes = [];

            foreach ($offers as $offer) {
                if ($offer->vendorCode !== null && trim($offer->vendorCode) !== '') {
                    $vendorCodes[trim($offer->vendorCode)] = true;
                }
            }

            $matched = 0;
            $products = Product::query()
                ->whereNotNull('sku')
                ->where('sku', '!=', '')
                ->pluck('sku');

            foreach ($products as $sku) {
                $normalizedSku = trim((string) $sku);
                if (isset($offerIds[$normalizedSku]) || isset($vendorCodes[$normalizedSku])) {
                    $matched++;
                }
            }

            $this->feedInfo = [
                'status' => 'ok',
                'checked_at' => now()->format('Y-m-d H:i:s'),
                'offers_count' => count($offers),
                'matched_count' => $matched,
                'not_found_count' => max(0, $products->count() - $matched),
                'error' => null,
            ];

            Notification::make()
                ->title('Фид MarketRadar проверен')
                ->body('Offers: '.count($offers).', matched: '.$matched)
                ->success()
                ->send();
        } catch (Throwable $e) {
            $this->feedInfo = [
                'status' => 'failed',
                'checked_at' => now()->format('Y-m-d H:i:s'),
                'offers_count' => 0,
                'matched_count' => 0,
                'not_found_count' => 0,
                'error' => $e->getMessage(),
            ];

            Notification::make()
                ->title('Ошибка проверки MarketRadar')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }

    public function dryRunSku(): void
    {
        $this->runForSku(['--dry-run' => true], 'Dry-run SKU выполнен');
    }

    public function syncSku(): void
    {
        $this->runForSku([], 'SKU обновлен из MarketRadar');
    }

    public function bulkDryRun(): void
    {
        $this->runCommand([
            '--limit' => $this->safeLimit(),
            '--dry-run' => true,
        ], 'Массовый dry-run выполнен');
    }

    public function syncPricesStock(): void
    {
        $this->runCommand([
            '--limit' => $this->safeLimit(),
            '--no-photos' => true,
        ], 'Цены и остатки обновлены');
    }

    public function syncPhotos(): void
    {
        $this->runCommand([
            '--limit' => $this->safeLimit(),
            '--photos' => true,
            '--no-prices' => true,
            '--no-stock' => true,
            '--only-missing-photos' => true,
        ], 'Фото MarketRadar обработаны');
    }

    public function syncAll(): void
    {
        $this->runCommand([
            '--limit' => $this->safeLimit(),
            '--all' => true,
        ], 'MarketRadar sync выполнен');
    }

    private function runForSku(array $options, string $title): void
    {
        $sku = trim($this->sku);
        if ($sku === '') {
            Notification::make()
                ->title('Укажите SKU')
                ->warning()
                ->send();

            return;
        }

        $this->runCommand([
            '--sku' => $sku,
            '--limit' => 1,
            ...$options,
        ], $title);
    }

    private function runCommand(array $arguments, string $title): void
    {
        try {
            Artisan::call('marketradar:sync', $arguments);
            $this->commandOutput = trim(Artisan::output());

            Notification::make()
                ->title($title)
                ->body((string) str($this->commandOutput ?: 'Команда выполнена.')->limit(700))
                ->success()
                ->send();
        } catch (Throwable $e) {
            $this->commandOutput = $e->getMessage();

            Notification::make()
                ->title('Ошибка MarketRadar')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }

    private function safeLimit(): int
    {
        return max(1, min(100, (int) $this->limit));
    }
}
