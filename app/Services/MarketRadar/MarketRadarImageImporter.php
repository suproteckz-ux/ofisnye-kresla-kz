<?php

namespace App\Services\MarketRadar;

use App\Models\Product;
use App\Models\ProductImage;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class MarketRadarImageImporter
{
    private const CONNECT_TIMEOUT = 8;
    private const READ_TIMEOUT = 35;
    private const MAX_SIZE_BYTES = 12 * 1024 * 1024;
    private const QUALITY = 82;
    private const RESPONSIVE_WIDTHS = [320, 640, 800, 960, 1200, 1280, 1600];
    private const THUMB_WIDTH = 160;

    public function import(Product $product, array $photoUrls, bool $dryRun = false, bool $forcePhotos = false): array
    {
        $product->loadMissing('images');

        $stats = [
            'photos_found' => count($photoUrls),
            'photos_downloaded' => 0,
            'photos_saved' => 0,
            'duplicates_skipped' => 0,
            'main_image_changed' => false,
            'errors' => [],
        ];

        if ($photoUrls === []) {
            return $stats;
        }

        if ($forcePhotos && ! $dryRun) {
            $this->deleteMarketRadarGalleryImages($product, $photoUrls);
            $product->load('images');
        }

        $existingSourceUrls = $product->images
            ->pluck('source_url')
            ->filter()
            ->map(fn ($url) => $this->normalizeUrl((string) $url))
            ->flip()
            ->all();

        $existingHashes = $product->images
            ->pluck('source_hash')
            ->filter()
            ->flip()
            ->all();

        foreach ($this->existingImageHashes($product) as $hash) {
            $existingHashes[$hash] = true;
        }

        $seenUrls = [];
        $nextSortOrder = ((int) ProductImage::where('product_id', $product->id)->max('sort_order')) + 1;
        $mainImageIsEmpty = trim((string) $product->main_image) === '';

        foreach ($photoUrls as $photoUrl) {
            $photoUrl = $this->normalizeUrl((string) $photoUrl);
            if ($photoUrl === '' || isset($seenUrls[$photoUrl])) {
                $stats['duplicates_skipped']++;
                continue;
            }

            $seenUrls[$photoUrl] = true;

            if (isset($existingSourceUrls[$photoUrl])) {
                $stats['duplicates_skipped']++;
                continue;
            }

            $download = $this->download($photoUrl);
            if (! $download['ok']) {
                $stats['errors'][] = ($download['error'] ?? 'download_failed') . ": {$photoUrl}";
                continue;
            }

            $stats['photos_downloaded']++;
            $hash = hash('sha256', $download['content']);

            if (isset($existingHashes[$hash])) {
                $stats['duplicates_skipped']++;
                continue;
            }

            $existingHashes[$hash] = true;

            if ($dryRun) {
                $stats['photos_saved']++;
                if ($mainImageIsEmpty && ! $stats['main_image_changed']) {
                    $stats['main_image_changed'] = true;
                    $mainImageIsEmpty = false;
                }
                continue;
            }

            $baseName = (string) Str::uuid();
            $directory = "products/{$product->id}/marketradar";
            $extension = $download['extension'] ?: 'jpg';
            $originalPath = "{$directory}/{$baseName}.{$extension}";
            $webpPath = "{$directory}/{$baseName}.webp";

            Storage::disk('public')->put($originalPath, $download['content']);
            $absoluteOriginal = Storage::disk('public')->path($originalPath);
            $createdWebp = $this->makeWebp($absoluteOriginal, Storage::disk('public')->path($webpPath), 1600);

            foreach (self::RESPONSIVE_WIDTHS as $width) {
                $this->makeWebp($absoluteOriginal, Storage::disk('public')->path($this->variantPath($webpPath, $width)), $width);
            }

            $this->makeWebp($absoluteOriginal, Storage::disk('public')->path($this->variantPath($webpPath, self::THUMB_WIDTH, 'thumb')), self::THUMB_WIDTH);
            $finalWebpPath = $createdWebp ? $webpPath : null;

            if ($mainImageIsEmpty && ! $stats['main_image_changed']) {
                $product->forceFill([
                    'main_image' => $originalPath,
                    'main_image_webp' => $finalWebpPath,
                    'main_image_alt' => $product->main_image_alt ?: $product->name,
                ])->save();

                $stats['main_image_changed'] = true;
                $mainImageIsEmpty = false;
                $stats['photos_saved']++;
                continue;
            }

            ProductImage::create([
                'product_id' => $product->id,
                'path' => $originalPath,
                'path_webp' => $finalWebpPath,
                'source_url' => $photoUrl,
                'source_hash' => $hash,
                'alt' => $product->name,
                'sort_order' => $nextSortOrder++,
            ]);

            $stats['photos_saved']++;
        }

        return $stats;
    }

    private function deleteMarketRadarGalleryImages(Product $product, array $photoUrls): void
    {
        $sourceUrls = array_map(fn ($url): string => $this->normalizeUrl((string) $url), $photoUrls);

        $images = $product->images()
            ->whereIn('source_url', $sourceUrls)
            ->get();

        foreach ($images as $image) {
            foreach (array_filter([$image->path, $image->path_webp]) as $path) {
                Storage::disk('public')->delete(ltrim((string) $path, '/'));
            }
            $image->delete();
        }
    }

    private function download(string $url): array
    {
        if (! filter_var($url, FILTER_VALIDATE_URL)) {
            return ['ok' => false, 'error' => 'invalid_url'];
        }

        try {
            $response = Http::connectTimeout(self::CONNECT_TIMEOUT)
                ->timeout(self::READ_TIMEOUT)
                ->withHeaders([
                    'User-Agent' => 'Mozilla/5.0 Chrome/125.0',
                    'Accept' => 'image/avif,image/webp,image/apng,image/svg+xml,image/*,*/*;q=0.8',
                ])
                ->get($url);
        } catch (\Throwable $e) {
            return ['ok' => false, 'error' => $e->getMessage()];
        }

        if (! $response->successful()) {
            return ['ok' => false, 'error' => 'http_' . $response->status()];
        }

        $content = $response->body();
        if ($content === '' || strlen($content) > self::MAX_SIZE_BYTES) {
            return ['ok' => false, 'error' => 'invalid_image_size'];
        }

        if (! $this->isValidImageContent($content)) {
            return ['ok' => false, 'error' => 'invalid_image_content'];
        }

        return [
            'ok' => true,
            'content' => $content,
            'extension' => $this->extensionFromContent($content) ?: $this->extensionFromMime((string) $response->header('Content-Type')),
        ];
    }

    private function makeWebp(string $source, string $target, int $maxWidth): bool
    {
        if (class_exists(\Imagick::class)) {
            try {
                $image = new \Imagick($source);
                if (method_exists($image, 'autoOrient')) {
                    $image->autoOrient();
                } elseif (method_exists($image, 'autoOrientImage')) {
                    $image->autoOrientImage();
                }
                if ($image->getImageWidth() > $maxWidth) {
                    $image->resizeImage($maxWidth, 0, \Imagick::FILTER_LANCZOS, 1);
                }
                $image->setImageFormat('webp');
                $image->setImageCompressionQuality(self::QUALITY);
                $this->ensureDirectory($target);
                $image->writeImage($target);
                $image->clear();

                return true;
            } catch (\Throwable) {
                return false;
            }
        }

        if (! function_exists('imagewebp')) {
            return false;
        }

        $info = @getimagesize($source);
        if (! $info) {
            return false;
        }

        $sourceImage = match ($info[2]) {
            IMAGETYPE_JPEG => function_exists('imagecreatefromjpeg') ? @imagecreatefromjpeg($source) : false,
            IMAGETYPE_PNG => function_exists('imagecreatefrompng') ? @imagecreatefrompng($source) : false,
            IMAGETYPE_WEBP => function_exists('imagecreatefromwebp') ? @imagecreatefromwebp($source) : false,
            default => false,
        };

        if (! $sourceImage) {
            return false;
        }

        $width = imagesx($sourceImage);
        $height = imagesy($sourceImage);
        $targetImage = $sourceImage;

        if ($width > $maxWidth) {
            $newWidth = $maxWidth;
            $newHeight = (int) round($height * ($newWidth / $width));
            $targetImage = imagecreatetruecolor($newWidth, $newHeight);
            imagealphablending($targetImage, false);
            imagesavealpha($targetImage, true);
            imagecopyresampled($targetImage, $sourceImage, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);
        }

        $this->ensureDirectory($target);
        $result = imagewebp($targetImage, $target, self::QUALITY);

        if ($targetImage !== $sourceImage) {
            imagedestroy($targetImage);
        }
        imagedestroy($sourceImage);

        return (bool) $result;
    }

    private function existingImageHashes(Product $product): array
    {
        $paths = array_filter([
            $product->main_image,
            $product->main_image_webp,
            ...$product->images->pluck('path')->all(),
            ...$product->images->pluck('path_webp')->all(),
        ]);

        $hashes = [];
        foreach ($paths as $path) {
            $path = ltrim((string) $path, '/');
            if ($path === '' || ! Storage::disk('public')->exists($path)) {
                continue;
            }

            $hash = @hash_file('sha256', Storage::disk('public')->path($path));
            if ($hash) {
                $hashes[] = $hash;
            }
        }

        return $hashes;
    }

    private function variantPath(string $webpPath, int $width, ?string $suffix = null): string
    {
        $directory = trim(dirname($webpPath), '.\\/');
        $name = pathinfo($webpPath, PATHINFO_FILENAME);
        $variantSuffix = $suffix ?: (string) $width;
        $filename = "{$name}-{$variantSuffix}.webp";

        return ($directory !== '' ? $directory . '/' : '') . $filename;
    }

    private function normalizeUrl(string $url): string
    {
        return trim(str_replace(['\\/', '\u002F'], '/', $url));
    }

    private function isValidImageContent(string $content): bool
    {
        $header = substr($content, 0, 12);

        return str_starts_with($header, "\xFF\xD8\xFF")
            || str_starts_with($header, "\x89PNG")
            || (str_starts_with($header, 'RIFF') && substr($header, 8, 4) === 'WEBP')
            || str_starts_with($header, 'GIF8');
    }

    private function extensionFromContent(string $content): ?string
    {
        $header = substr($content, 0, 12);

        return match (true) {
            str_starts_with($header, "\xFF\xD8\xFF") => 'jpg',
            str_starts_with($header, "\x89PNG") => 'png',
            str_starts_with($header, 'RIFF') && substr($header, 8, 4) === 'WEBP' => 'webp',
            str_starts_with($header, 'GIF8') => 'gif',
            default => null,
        };
    }

    private function extensionFromMime(string $mime): ?string
    {
        return match (true) {
            str_contains($mime, 'jpeg'), str_contains($mime, 'jpg') => 'jpg',
            str_contains($mime, 'png') => 'png',
            str_contains($mime, 'webp') => 'webp',
            str_contains($mime, 'gif') => 'gif',
            default => null,
        };
    }

    private function ensureDirectory(string $path): void
    {
        $directory = dirname($path);
        if (! is_dir($directory)) {
            mkdir($directory, 0775, true);
        }
    }
}
