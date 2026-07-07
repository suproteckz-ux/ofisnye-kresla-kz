@props([
    'image' => null,
    'webp' => null,
    'alt' => '',
    'class' => '',
    'loading' => 'lazy',
    'fetchpriority' => null,
    'width' => 720,
    'height' => 450,
    'sizes' => '(max-width: 768px) 100vw, 50vw',
])

@php
    $image = ltrim((string) $image, '/');
    $webp = ltrim((string) $webp, '/');
    $disk = \Illuminate\Support\Facades\Storage::disk('public');
    $webpSrcset = [];
    $avifSrcset = [];

    if ($webp !== '') {
        $directory = trim(dirname($webp), '.\\/');
        $name = pathinfo($webp, PATHINFO_FILENAME);

        foreach ([320, 640, 960, 1280] as $variantWidth) {
            $webpVariant = ($directory !== '' ? $directory.'/' : '').$name.'-'.$variantWidth.'.webp';
            $avifVariant = ($directory !== '' ? $directory.'/' : '').$name.'-'.$variantWidth.'.avif';

            if ($disk->exists($webpVariant)) {
                $webpSrcset[] = asset('storage/'.$webpVariant).' '.$variantWidth.'w';
            }

            if ($disk->exists($avifVariant)) {
                $avifSrcset[] = asset('storage/'.$avifVariant).' '.$variantWidth.'w';
            }
        }
    }
@endphp

@if($image !== '')
<picture>
    @if($avifSrcset)
    <source srcset="{{ implode(', ', $avifSrcset) }}" sizes="{{ $sizes }}" type="image/avif">
    @endif
    @if($webp !== '')
    <source srcset="{{ $webpSrcset ? implode(', ', $webpSrcset) : asset('storage/'.$webp) }}" sizes="{{ $sizes }}" type="image/webp">
    @endif
    <img src="{{ asset('storage/'.$image) }}"
         alt="{{ $alt }}"
         class="{{ $class }}"
         loading="{{ $loading }}"
         decoding="async"
         width="{{ $width }}"
         height="{{ $height }}"
         @if($fetchpriority) fetchpriority="{{ $fetchpriority }}" @endif>
</picture>
@endif
