<?php

namespace App\Console\Commands;

use App\Models\Product;
use App\Models\ProductImage;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class OptimizeProductImagesCommand extends Command
{
    protected $signature = 'images:optimize-products {--dry-run : Show expected changes without writing files} {--limit=50 : Maximum images to process} {--force : Rebuild existing WebP files}';

    protected $description = 'Generate optimized WebP versions for product images without deleting originals.';

    private const QUALITY = 82;
    private const MAX_WIDTH = 1600;

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $force = (bool) $this->option('force');
        $limit = max(1, (int) $this->option('limit'));
        $processed = 0;
        $skipped = 0;
        $savedBytes = 0;
        $large = ['300kb' => 0, '500kb' => 0, '1mb' => 0];

        $this->info(($dryRun ? '[dry-run] ' : '') . "Checking up to {$limit} product images...");

        foreach ($this->imageRecords() as $record) {
            if ($processed >= $limit) {
                break;
            }

            $sourcePath = $record['path'];
            if (! $sourcePath || ! Storage::disk('public')->exists($sourcePath)) {
                $skipped++;
                continue;
            }

            $absoluteSource = Storage::disk('public')->path($sourcePath);
            $oldSize = filesize($absoluteSource) ?: 0;
            $this->countLargeImage($oldSize, $large);

            $targetPath = $record['webp_path'] ?: $this->webpPath($sourcePath);
            $absoluteTarget = Storage::disk('public')->path($targetPath);

            if (! $force && Storage::disk('public')->exists($targetPath)) {
                $skipped++;
                continue;
            }

            $newBytes = $this->makeWebp($absoluteSource, $absoluteTarget, $dryRun);
            if ($newBytes === null) {
                $skipped++;
                continue;
            }

            $processed++;
            $saved = max(0, $oldSize - $newBytes);
            $savedBytes += $saved;

            if (! $dryRun) {
                $record['model']->forceFill([$record['field'] => $targetPath])->save();
            }

            $percent = $oldSize > 0 ? round(($saved / $oldSize) * 100, 1) : 0;
            $this->line(sprintf(
                '%s: %s -> %s, saved %s (%s%%)',
                $sourcePath,
                $this->formatBytes($oldSize),
                $this->formatBytes($newBytes),
                $this->formatBytes($saved),
                $percent
            ));
        }

        $this->newLine();
        $this->info("Processed: {$processed}; skipped: {$skipped}; total saved: " . $this->formatBytes($savedBytes));
        $this->line("Large originals: >300KB {$large['300kb']}, >500KB {$large['500kb']}, >1MB {$large['1mb']}");

        return self::SUCCESS;
    }

    private function imageRecords(): \Generator
    {
        foreach (Product::query()
            ->whereNotNull('main_image')
            ->where('main_image', '!=', '')
            ->select(['id', 'main_image', 'main_image_webp'])
            ->lazyById(200) as $product) {
            yield [
                'model' => $product,
                'path' => $product->main_image,
                'webp_path' => $product->main_image_webp,
                'field' => 'main_image_webp',
            ];
        }

        foreach (ProductImage::query()
            ->whereNotNull('path')
            ->where('path', '!=', '')
            ->select(['id', 'path', 'path_webp'])
            ->lazyById(200) as $image) {
            yield [
                'model' => $image,
                'path' => $image->path,
                'webp_path' => $image->path_webp,
                'field' => 'path_webp',
            ];
        }
    }

    private function makeWebp(string $source, string $target, bool $dryRun): ?int
    {
        $extension = strtolower(pathinfo($source, PATHINFO_EXTENSION));

        if (class_exists(\Imagick::class)) {
            try {
                $image = new \Imagick($source);
                if (method_exists($image, 'autoOrient')) {
                    $image->autoOrient();
                } elseif (method_exists($image, 'autoOrientImage')) {
                    $image->autoOrientImage();
                }
                if ($image->getImageWidth() > self::MAX_WIDTH) {
                    $image->resizeImage(self::MAX_WIDTH, 0, \Imagick::FILTER_LANCZOS, 1);
                }
                $image->setImageFormat('webp');
                $image->setImageCompressionQuality(self::QUALITY);
                $blob = $image->getImagesBlob();
                $image->clear();

                if (! $dryRun) {
                    $this->ensureDirectory($target);
                    file_put_contents($target, $blob);
                }

                return strlen($blob);
            } catch (\Throwable) {
                return null;
            }
        }

        if (! function_exists('imagewebp')) {
            return null;
        }

        $sourceImage = match ($extension) {
            'jpg', 'jpeg' => function_exists('imagecreatefromjpeg') ? @imagecreatefromjpeg($source) : false,
            'png' => function_exists('imagecreatefrompng') ? @imagecreatefrompng($source) : false,
            'webp' => function_exists('imagecreatefromwebp') ? @imagecreatefromwebp($source) : false,
            default => false,
        };

        if (! $sourceImage) {
            return null;
        }

        $width = imagesx($sourceImage);
        $height = imagesy($sourceImage);
        $targetImage = $sourceImage;

        if ($width > self::MAX_WIDTH) {
            $newWidth = self::MAX_WIDTH;
            $newHeight = (int) round($height * ($newWidth / $width));
            $targetImage = imagecreatetruecolor($newWidth, $newHeight);
            imagealphablending($targetImage, false);
            imagesavealpha($targetImage, true);
            imagecopyresampled($targetImage, $sourceImage, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);
        }

        ob_start();
        imagewebp($targetImage, null, self::QUALITY);
        $blob = ob_get_clean();

        if ($targetImage !== $sourceImage) {
            imagedestroy($targetImage);
        }
        imagedestroy($sourceImage);

        if ($blob === false) {
            return null;
        }

        if (! $dryRun) {
            $this->ensureDirectory($target);
            file_put_contents($target, $blob);
        }

        return strlen($blob);
    }

    private function webpPath(string $sourcePath): string
    {
        $directory = trim(dirname($sourcePath), '.\\/');
        $filename = pathinfo($sourcePath, PATHINFO_FILENAME) . '.webp';

        return ($directory !== '' ? $directory . '/' : '') . $filename;
    }

    private function ensureDirectory(string $path): void
    {
        $directory = dirname($path);
        if (! is_dir($directory)) {
            mkdir($directory, 0775, true);
        }
    }

    private function countLargeImage(int $bytes, array &$large): void
    {
        if ($bytes > 300 * 1024) {
            $large['300kb']++;
        }
        if ($bytes > 500 * 1024) {
            $large['500kb']++;
        }
        if ($bytes > 1024 * 1024) {
            $large['1mb']++;
        }
    }

    private function formatBytes(int $bytes): string
    {
        if ($bytes >= 1024 * 1024) {
            return round($bytes / 1024 / 1024, 2) . ' MB';
        }

        return round($bytes / 1024, 1) . ' KB';
    }
}
