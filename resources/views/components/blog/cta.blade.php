@props(['variant' => 'default'])

@php
    $wa = \App\Services\CacheService::setting('whatsapp', '');
    $title = $variant === 'catalog' ? 'Подобрать кресло под задачу?' : 'Не знаете, какое кресло выбрать?';
    $text = $variant === 'catalog'
        ? 'Откройте каталог и сравните модели по цене, материалу и назначению.'
        : 'Напишите нам в WhatsApp — поможем выбрать удобную модель под рост, бюджет и формат работы.';
@endphp

<aside class="blog-cta">
    <div>
        <h2>{{ $title }}</h2>
        <p>{{ $text }}</p>
    </div>
    <div class="blog-cta__actions">
        @if($wa)
        <a class="btn-wa" href="https://wa.me/{{ $wa }}?text={{ urlencode('Здравствуйте! Помогите подобрать офисное кресло') }}" target="_blank" rel="noopener" data-analytics-location="blog_cta">Написать в WhatsApp</a>
        @endif
        <a class="btn-orange" href="{{ url('/ofisnye-kresla') }}">Перейти в каталог</a>
    </div>
</aside>
