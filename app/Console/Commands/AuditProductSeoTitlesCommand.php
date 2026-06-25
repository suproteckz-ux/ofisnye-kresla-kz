<?php

namespace App\Console\Commands;

use App\Models\Product;
use Illuminate\Console\Command;

class AuditProductSeoTitlesCommand extends Command
{
    protected $signature = 'seo:audit-product-titles {--only-problematic : Show only problematic rows}';

    protected $description = 'Audit product meta titles and final SEO titles.';

    public function handle(): int
    {
        $rows = [];
        $storedTooLong = 0;
        $storedRepeated = 0;
        $storedEmpty = 0;
        $finalTooLong = 0;
        $finalRepeated = 0;
        $finalEmpty = 0;

        Product::query()
            ->select(['id', 'sku', 'slug', 'name', 'meta_title'])
            ->orderBy('id')
            ->chunk(500, function ($products) use (
                &$rows,
                &$storedTooLong,
                &$storedRepeated,
                &$storedEmpty,
                &$finalTooLong,
                &$finalRepeated,
                &$finalEmpty
            ) {
                foreach ($products as $product) {
                    $metaTitle = trim((string) $product->getRawOriginal('meta_title'));
                    $finalTitle = $product->seoTitle();
                    $finalReasons = $product->seoTitleAuditReasons();
                    $storedReasons = $this->titleReasons($metaTitle);

                    $storedTooLong += in_array('too_long', $storedReasons, true) ? 1 : 0;
                    $storedRepeated += in_array('repeated_phrase', $storedReasons, true) ? 1 : 0;
                    $storedEmpty += in_array('empty', $storedReasons, true) ? 1 : 0;
                    $finalTooLong += in_array('too_long', $finalReasons, true) ? 1 : 0;
                    $finalRepeated += in_array('repeated_phrase', $finalReasons, true) ? 1 : 0;
                    $finalEmpty += in_array('empty', $finalReasons, true) ? 1 : 0;

                    if ($this->option('only-problematic') && $storedReasons === [] && $finalReasons === []) {
                        continue;
                    }

                    $rows[] = [
                        'id' => $product->id,
                        'sku' => $product->sku,
                        'slug' => $product->slug,
                        'name' => $product->name,
                        'meta_title' => $metaTitle,
                        'final_title' => $finalTitle,
                        'length' => mb_strlen($finalTitle),
                        'stored_issues' => implode(',', $storedReasons),
                        'final_issues' => implode(',', $finalReasons),
                    ];
                }
            });

        $this->table(
            ['id', 'sku', 'slug', 'name', 'meta_title', 'final_title', 'length', 'stored_issues', 'final_issues'],
            $rows
        );

        $this->newLine();
        $this->info('Stored meta_title issues:');
        $this->line("title > 70: {$storedTooLong}");
        $this->line("empty title: {$storedEmpty}");
        $this->line("repeated phrase: {$storedRepeated}");

        $this->newLine();
        $this->info('Final seoTitle() issues:');
        $this->line("title > 70: {$finalTooLong}");
        $this->line("empty title: {$finalEmpty}");
        $this->line("repeated phrase: {$finalRepeated}");

        return self::SUCCESS;
    }

    private function titleReasons(string $title): array
    {
        $reasons = [];
        if ($title === '') {
            $reasons[] = 'empty';
        }
        if (mb_strlen($title) > 70) {
            $reasons[] = 'too_long';
        }
        if (preg_match_all('/офисные\s+кресла/iu', $title) > 1) {
            $reasons[] = 'repeated_phrase';
        }
        if (preg_match('/где\s+выгодно\s+приобрести|заказать\s+офисные\s+кресла|офисные\s+кресла\s+магазин/iu', $title) === 1) {
            $reasons[] = 'forbidden_phrase';
        }

        return $reasons;
    }
}
