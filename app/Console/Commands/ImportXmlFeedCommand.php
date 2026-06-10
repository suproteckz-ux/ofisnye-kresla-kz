<?php

namespace App\Console\Commands;

use App\Models\Brand;
use App\Models\Category;
use App\Models\ImportBatch;
use App\Services\Import\XmlFeedParser;
use App\Services\Import\FullProductImporter;
use App\Services\Import\ImageDownloader;
use App\Services\CacheService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Импорт офисных кресел из Google Merchant XML фида.
 *
 * ТОЛЬКО РУЧНОЙ ЗАПУСК. Автоматического расписания нет.
 *
 * Использование:
 *   php artisan import:xml-feed
 *   php artisan import:xml-feed --dry-run
 *   php artisan import:xml-feed --prices-only --no-images
 *   php artisan import:xml-feed --url="https://..."
 */
class ImportXmlFeedCommand extends Command
{
    protected $signature = 'import:xml-feed
        {--url= : URL XML фида (по умолчанию из IMPORT_XML_URL в .env)}
        {--dry-run : Только парсинг, без сохранения в БД}
        {--prices-only : Только цены и наличие, без создания новых товаров}
        {--no-images : Не скачивать изображения}
        {--chunk=50 : Размер чанка обработки}';

    protected $description = 'Ручной импорт офисных кресел из Google Merchant XML фида';

    /** Маппинг ключевых слов из g:product_type → slug категории */
    private const CATEGORY_MAP = [
        'руководит'  => 'kresla-rukovoditelya',
        'директор'   => 'kresla-rukovoditelya',
        'эргономич'  => 'ergonomichnye-kresla',
        'ортопед'    => 'ergonomichnye-kresla',
        'игров'      => 'igrovye-kresla',
        'геймер'     => 'igrovye-kresla',
        'компьютер'  => 'kompyuternye-kresla',
        'офис'       => 'ofisnye-kresla',
        'кресл'      => 'ofisnye-kresla',
        'стул'       => 'ofisnye-kresla',
    ];

    public function handle(XmlFeedParser $parser): int
    {
        $startTime = microtime(true);

        $url = $this->option('url') ?? config('import.xml_url', env('IMPORT_XML_URL'));
        if (empty($url)) {
            $this->error('URL не задан. Укажите --url="..." или задайте IMPORT_XML_URL в .env');
            return self::FAILURE;
        }

        $isDryRun   = (bool) $this->option('dry-run');
        $pricesOnly = (bool) $this->option('prices-only');
        $noImages   = (bool) $this->option('no-images');
        $chunkSize  = (int)  $this->option('chunk');

        $this->line('');
        $this->line('┌─────────────────────────────────────────────┐');
        $this->line('│     Импорт XML фида — Офисные кресла        │');
        $this->line('└─────────────────────────────────────────────┘');
        if ($isDryRun)   $this->warn('  Режим: DRY RUN (без сохранения)');
        if ($pricesOnly) $this->warn('  Режим: только цены и наличие');
        if ($noImages)   $this->warn('  Режим: без загрузки изображений');
        $this->line("  URL: {$url}");
        $this->line('');

        // 1. Загрузка и парсинг XML
        $this->info('📥 Загрузка XML...');
        try {
            $rows = $parser->fetchAndParse($url);
        } catch (\Throwable $e) {
            $this->error('❌ Ошибка загрузки: ' . $e->getMessage());
            Log::error('import:xml-feed fetch error', ['error' => $e->getMessage()]);
            return self::FAILURE;
        }

        $total = count($rows);
        $this->info("   Найдено в фиде: {$total} товаров");

        if ($total === 0) {
            $this->warn('⚠️  Фид пустой. Проверьте URL.');
            return self::FAILURE;
        }

        if ($isDryRun) {
            $this->line('');
            $this->table(
                ['SKU', 'Название', 'Цена ₸', 'Категория', 'Материал', 'Фото'],
                array_slice(array_map(fn($r) => [
                    $r['sku'],
                    mb_substr($r['name'], 0, 40),
                    number_format($r['price'], 0, '.', ' '),
                    implode(' > ', array_slice($r['category_path'], -2)),
                    $r['attributes']['Основной обивочный материал'] ?? '—',
                    empty($r['main_image']) ? '❌' : '✅',
                ], $rows), 0, 20)
            );
            $this->line('');
            $this->info("✅ DRY RUN завершён. В фиде {$total} товаров — сохранение не выполнялось.");
            return self::SUCCESS;
        }

        // 2. Создать ImportBatch
        $batch = ImportBatch::create([
            'type'       => $pricesOnly ? 'prices_only' : 'full',
            'filename'   => 'xml_' . now()->format('Y-m-d_H-i-s') . '.xml',
            'filepath'   => 'xml_feed',
            'status'     => 'processing',
            'total_rows' => $total,
            'started_at' => now(),
        ]);

        // 3. Категории (при полном импорте)
        if (!$pricesOnly) {
            $this->info('📁 Синхронизация категорий...');
            $this->ensureCategories();
        }

        // 4. Импорт чанками
        $this->info('⚙️  Обработка товаров...');
        $importer   = new FullProductImporter($batch);
        $stats      = ['created' => 0, 'updated' => 0, 'skipped' => 0, 'errors' => 0];
        $imageQueue = [];
        $chunks     = array_chunk($rows, $chunkSize);
        $bar        = $this->output->createProgressBar(count($chunks));
        $bar->start();

        foreach ($chunks as $chunk) {
            $prepared = $this->prepareRows($chunk, $pricesOnly);
            $result   = $importer->processChunk($prepared);

            $stats['created'] += $result['created'];
            $stats['updated'] += $result['updated'];
            $stats['skipped'] += $result['skipped'];
            $stats['errors']  += $result['errors'];

            foreach ($result['image_queue'] ?? [] as $item) {
                $imageQueue[] = $item;
            }
            $bar->advance();
        }
        $bar->finish();
        $this->line('');

        // 5. Изображения
        $imgOk = 0;
        if (!$noImages && !empty($imageQueue)) {
            $this->info('🖼️  Загрузка изображений (' . count($imageQueue) . ')...');
            $imgBar = $this->output->createProgressBar(count($imageQueue));
            $imgBar->start();
            $downloader = new ImageDownloader();

            foreach ($imageQueue as $item) {
                try {
                    $imgUrl = $item['image_url'] ?? $item['url'] ?? '';
                    if ($imgUrl) {
                        $downloader->download($item['product_id'], $imgUrl, true);
                        $imgOk++;
                    }
                } catch (\Throwable $e) {
                    Log::warning('Image download failed: ' . $e->getMessage());
                }
                $imgBar->advance();
            }
            $imgBar->finish();
            $this->line('');
        }

        // 6. Завершить batch
        $batch->update([
            'status'        => 'done',
            'finished_at'   => now(),
            'created_count' => $stats['created'],
            'updated_count' => $stats['updated'],
            'skipped_count' => $stats['skipped'],
            'error_count'   => $stats['errors'],
        ]);

        // 7. Сбросить кэш
        CacheService::forgetAll();

        // 8. Итоговый отчёт
        $elapsed = round(microtime(true) - $startTime, 1);
        $this->line('');
        $this->line('┌─────────────────────────────────────────────┐');
        $this->line('│            Отчёт об импорте                 │');
        $this->line('└─────────────────────────────────────────────┘');
        $this->table(['Показатель', 'Значение'], [
            ['Найдено товаров в фиде', $total],
            ['✅ Создано новых',        $stats['created']],
            ['🔄 Обновлено',            $stats['updated']],
            ['⏭️  Пропущено',            $stats['skipped']],
            ['❌ Ошибок',               $stats['errors']],
            ['🖼️  Загружено фото',       $imgOk],
            ['⏱️  Время выполнения',     $elapsed . ' сек'],
            ['🗃️  Batch ID',             $batch->id],
        ]);

        if ($stats['errors'] > 0) {
            $this->warn("⚠️  {$stats['errors']} ошибок. Подробности: php artisan tinker → ImportError::where('batch_id', {$batch->id})->get()");
        } else {
            $this->info('✅ Импорт завершён без ошибок.');
        }

        Log::info('import:xml-feed manual run completed', array_merge($stats, [
            'batch_id' => $batch->id,
            'images'   => $imgOk,
            'elapsed'  => $elapsed,
        ]));

        return $stats['errors'] > ($total * 0.5) ? self::FAILURE : self::SUCCESS;
    }

    private function ensureCategories(): void
    {
        $root = Category::firstOrCreate(
            ['slug' => 'ofisnye-kresla'],
            ['name' => 'Офисные кресла', 'is_active' => true, 'sort_order' => 0,
             'meta_title' => 'Офисные кресла в Алматы — купить с доставкой',
             'meta_description' => 'Офисные кресла в Алматы. Гарантия 12 мес, доставка по Казахстану.',
             'h1' => 'Офисные кресла в Алматы']
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

    /**
     * Преобразует строки XmlFeedParser в формат FullProductImporter.
     */
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
                // Передаём как PHP array — Eloquent cast 'array' сам сделает json_encode
                'attributes'  => !empty($row['attributes']) && is_array($row['attributes'])
                    ? $row['attributes']
                    : null,
            ];

            if (!$pricesOnly) {
                $slug = $this->resolveCategorySlug($row['category_path'] ?? []);
                if (!isset($catCache[$slug])) {
                    $catCache[$slug] = Category::where('slug', $slug)->value('id');
                }
                if ($catCache[$slug]) {
                    $r['category_id'] = $catCache[$slug];
                }
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
        foreach (array_reverse($path) as $segment) {
            $lower = mb_strtolower($segment);
            foreach (self::CATEGORY_MAP as $keyword => $slug) {
                if (mb_strpos($lower, $keyword) !== false) return $slug;
            }
        }
        return 'ofisnye-kresla';
    }
}
