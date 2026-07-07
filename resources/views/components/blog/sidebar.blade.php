@props(['toc' => [], 'categories' => []])

@php($wa = \App\Services\CacheService::setting('whatsapp', ''))

<aside class="blog-sidebar">
    <x-blog.toc :items="$toc" />

    @if($categories->count())
    <section class="blog-side-card">
        <div class="blog-side-title">Популярные категории</div>
        <div class="blog-side-links">
            @foreach($categories as $category)
            <a href="{{ $category->url }}">{{ $category->name }}</a>
            @endforeach
        </div>
    </section>
    @endif

    <section class="blog-side-card blog-side-cta">
        <div class="blog-side-title">Нужна помощь?</div>
        <p>Подскажем, какое кресло выбрать для дома, офиса или руководителя.</p>
        @if($wa)
        <a class="btn-wa-sm" href="https://wa.me/{{ $wa }}?text={{ urlencode('Здравствуйте! Нужна консультация по офисному креслу') }}" target="_blank" rel="noopener" data-analytics-location="blog_sidebar">WhatsApp</a>
        @endif
        <a class="blog-phone" href="tel:+77784921113" data-analytics-location="blog_sidebar">+7 778 492 11 13</a>
    </section>

    <section class="blog-side-card">
        <div class="blog-side-title">Почему удобно</div>
        <ul class="blog-checks">
            <li>Доставка по Алматы</li>
            <li>Помощь в подборе</li>
            <li>Гарантия 12 месяцев</li>
            <li>Документы для юрлиц</li>
        </ul>
    </section>

    <x-blog.share-buttons />
</aside>
