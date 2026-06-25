@props(['product'])

@php
    $sku = trim((string) ($product->sku ?? ''));
    $merchantCode = trim((string) (config('kaspi.merchant_code') ?: config('services.kaspi.merchant_code')));
    $cityCode = trim((string) (config('kaspi.city_code') ?: config('services.kaspi.city_code')));
    $template = trim((string) (config('kaspi.button_template') ?: config('services.kaspi.button_template') ?: 'button'));

    if ($template !== 'button') {
        $template = 'button';
    }
@endphp

@if($sku !== '' && $merchantCode !== '' && $cityCode !== '')
<div class="ks-widget"
     data-template="{{ $template }}"
     data-merchant-sku="{{ $sku }}"
     data-merchant-code="{{ $merchantCode }}"
     data-city="{{ $cityCode }}"
     data-style="desktop"></div>
@endif
