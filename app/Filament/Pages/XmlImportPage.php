<?php

namespace App\Filament\Pages;

use App\Models\ImportBatch;
use App\Services\Import\XmlFeedParser;
use App\Services\Import\FullProductImporter;
use App\Services\Import\ImageDownloader;
use App\Services\CacheService;
use App\Models\Brand;
use App\Models\Category;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Страница ручного импорта товаров из Google Merchant XML фида.
 * Доступна в: Admin → Импорт → Импорт XML фида
 */
class XmlImportPage extends Page
{
    public static function getNavigationIcon(): string
    {
        return 'heroicon-o-arrow-path';
    }

    public static function getNavigationGroup(): ?string
    {
        return 'Импорт';
    }

    public static function getNavigationLabel(): string
    {
        return 'Импорт XML фида';
    }

    public function getTitle(): string
    {
        return 'Импорт офисных кресел из XML фида';
    }

    public static function getNavigationSort(): int
    {
        return 2;
    }

    protected string $view = 'filament.pages.xml-import';

    // ── Состояние ──────────────────────────────────────────────────────

    public string $xmlUrl = '';
    public bool $pricesOnly = false;
    public bool $noImages = false;

    public ?array $report = null;
    public bool $isRunning = false;
    public string $statusMessage = '';

    public function mount(): void
    {
        $this->xmlUrl = config('import.xml_url', env('IMPORT_XML_URL', ''));
    }

    // ── Действия ───────────────────────────────────────────────────────

    /** Предварительный просмотр фида без сохранения */
    public function preview(): void
    {
        $this->report = null;
        $this->statusMessage = '';

        if (empty($this->xmlUrl)) {
            Notification::make()->title('URL фида не задан')->danger()->send();
            return;
        }

        try {
            $parser = new XmlFeedParser();
            $rows   = $parser->fetchAndParse($this->xmlUrl);
            $total  = count($rows);

            $this->report = [
                'mode'    => 'preview',
                'total'   => $total,
                'sample'  => array_slice(array_map(fn($r) => [
                    'sku'      => $r['sku'],
                    'name'     => mb_substr($r['name'], 0, 60),
                    'price'    => number_format($r['price'], 0, '.', ' ') . ' ₸',
                    'category' => implode(' > ', array_slice($r['category_path'], -2)),
                    'material' => $r['attributes']['Основной обивочный материал'] ?? '—',
                    'has_photo'=> !empty($r['main_image']) ? '✅' : '❌',
                ], $rows), 0, 20),
            ];

            Notification::make()
                ->title("В фиде {$total} товаров. Ниже показаны первые 20.")
                ->success()->send();

        } catch (\Throwable $e) {
            Notification::make()
                ->title('Ошибка загрузки XML')
                ->body($e->getMessage())
                ->danger()->send();
        }
    }

    /** Запустить полный импорт */
    public function runImport(): void
    {
        $this->report = null;

        if (empty($this->xmlUrl)) {
            Notification::make()->title('URL фида не задан')->danger()->send();
            return;
        }

        $startTime = microtime(true);

        try {
            $parser = new XmlFeedParser();
            $rows   = $parser->fetchAndParse($this->xmlUrl);
            $total  = count($rows);

            if ($total === 0) {
                Notification::make()->title('Фид пустой')->warning()->send();
                return;
            }

            // Создать batch
            $batch = ImportBatch::create([
                'type'       => $this->pricesOnly ? 'prices_only' : 'full',
                'filename'   => 'xml_' . now()->format('Y-m-d_H-i-s') . '.xml',
                'filepath'   => 'xml_feed',
                'status'     => 'processing',
                'total_rows' => $total,
                'started_at' => now(),
            ]);

            // Категории
            if (!$this->pricesOnly) {
                $this->ensureCategories();
            }

            // Импорт
            $importer   = new FullProductImporter($batch);
            $stats      = ['created' => 0, 'updated' => 0, 'skipped' => 0, 'errors' => 0];
            $imageQueue = [];

            foreach (array_chunk($rows, 50) as $chunk) {
                $prepared = $this->prepareRows($chunk, $this->pricesOnly);
                $result   = $importer->processChunk($prepared);

                $stats['created'] += $result['created'];
                $stats['updated'] += $result['updated'];
                $stats['skipped'] += $result['skipped'];
                $stats['errors']  += $result['errors'];

                foreach ($result['image_queue'] ?? [] as $item) {
                    $imageQueue[] = $item;
                }
            }

            // Изображения
            $imgOk = 0;
            if (!$this->noImages && !empty($imageQueue)) {
                $downloader = new ImageDownloader();
                foreach ($imageQueue as $item) {
                    try {
                        $url = $item['image_url'] ?? $item['url'] ?? '';
                        if ($url) { $downloader->download($item['product_id'], $url, true); $imgOk++; }
                    } catch (\Throwable $e) {
                        Log::warning('Image download error: ' . $e->getMessage());
                    }
                }
            }

            // Завершить batch
            $batch->update([
                'status'        => 'done',
                'finished_at'   => now(),
                'created_count' => $stats['created'],
                'updated_count' => $stats['updated'],
                'skipped_count' => $stats['skipped'],
                'error_count'   => $stats['errors'],
            ]);

            CacheService::forgetAll();

            $elapsed = round(microtime(true) - $startTime, 1);

            $this->report = [
                'mode'     => 'import',
                'total'    => $total,
                'created'  => $stats['created'],
                'updated'  => $stats['updated'],
                'skipped'  => $stats['skipped'],
                'errors'   => $stats['errors'],
                'images'   => $imgOk,
                'elapsed'  => $elapsed,
                'batch_id' => $batch->id,
            ];

            Notification::make()
                ->title("Импорт завершён за {$elapsed} сек")
                ->body("Создано: {$stats['created']}, обновлено: {$stats['updated']}, ошибок: {$stats['errors']}")
                ->success()->send();

        } catch (\Throwable $e) {
            Notification::make()
                ->title('Ошибка импорта')
                ->body($e->getMessage())
                ->danger()->send();

            Log::error('XmlImportPage error', ['error' => $e->getMessage()]);
        }
    }

    private function ensureCategories(): void
    {
        $root = Category::firstOrCreate(
            ['slug' => 'ofisnye-kresla'],
            ['name' => 'Офисные кресла', 'is_active' => true, 'sort_order' => 0]
        );

        foreach ([
            ['kresla-rukovoditelya', 'Кресла для руководителей', 1],
            ['ergonomichnye-kresla', 'Эргономичные кресла', 2],
            ['kompyuternye-kresla',  'Компьютерные кресла', 3],
            ['igrovye-kresla',       'Игровые кресла', 4],
        ] as [$slug, $name, $sort]) {
            Category::firstOrCreate(
                ['slug' => $slug],
                ['name' => $name, 'parent_id' => $root->id, 'is_active' => true, 'sort_order' => $sort]
            );
        }
    }

    private function prepareRows(array $rows, bool $pricesOnly): array
    {
        static $catCache = [], $brandCache = [];
        $prepared = [];

        foreach ($rows as $row) {
            $r = [
                'sku'         => $row['sku'],
                'name'        => $row['name'],
                'price'       => (float) $row['price'],
                'old_price'   => isset($row['old_price']) ? (float) $row['old_price'] : null,
                'in_stock'    => !empty($row['in_stock']),
                'quantity'    => !empty($row['in_stock']) ? 10 : 0,
                'description' => $row['description'] ?? null,
                'image_url'   => $row['main_image'] ?? null,
                'is_active'   => true,
                'attributes'  => !empty($row['attributes'])
                    ? json_encode($row['attributes'], JSON_UNESCAPED_UNICODE) : null,
            ];

            if (!$pricesOnly) {
                $slug = $this->resolveCategorySlug($row['category_path'] ?? []);
                if (!isset($catCache[$slug])) {
                    $catCache[$slug] = Category::where('slug', $slug)->value('id');
                }
                if ($catCache[$slug]) $r['category_id'] = $catCache[$slug];
                $r['category'] = Category::find($catCache[$slug] ?? null)?->name ?? 'Офисные кресла';

                $brandName = trim($row['brand'] ?? '');
                if ($brandName) {
                    if (!isset($brandCache[$brandName])) {
                        $brand = Brand::firstOrCreate(
                            ['name' => $brandName],
                            ['slug' => Str::slug($brandName), 'is_active' => true]
                        );
                        $brandCache[$brandName] = $brand->id;
                    }
                    $r['brand_id'] = $brandCache[$brandName];
                    $r['brand']    = $brandName;
                }
            }

            $prepared[] = $r;
        }

        return $prepared;
    }

    private function resolveCategorySlug(array $path): string
    {
        $map = [
            'руководит' => 'kresla-rukovoditelya', 'директор' => 'kresla-rukovoditelya',
            'эргономич' => 'ergonomichnye-kresla', 'ортопед'  => 'ergonomichnye-kresla',
            'игров'     => 'igrovye-kresla',         'геймер'   => 'igrovye-kresla',
            'компьютер' => 'kompyuternye-kresla',
            'офис'      => 'ofisnye-kresla',         'кресл'    => 'ofisnye-kresla',
        ];
        foreach (array_reverse($path) as $segment) {
            $lower = mb_strtolower($segment);
            foreach ($map as $kw => $slug) {
                if (mb_strpos($lower, $kw) !== false) return $slug;
            }
        }
        return 'ofisnye-kresla';
    }
}
