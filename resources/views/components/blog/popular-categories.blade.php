@props(['categories'])

@if($categories->count())
<section class="blog-section">
    <div class="blog-section__head">
        <div>
            <h2>Популярные категории</h2>
            <p>Быстрый переход к разделам каталога, которые чаще всего помогают после статьи.</p>
        </div>
    </div>
    <div class="blog-category-grid">
        @foreach($categories->take(4) as $category)
        <a class="blog-category-card" href="{{ $category->url }}">
            @if($category->image)
            <picture>
                @if($category->image_webp)
                <source srcset="{{ asset('storage/'.$category->image_webp) }}" type="image/webp">
                @endif
                <img src="{{ asset('storage/'.$category->image) }}" alt="{{ $category->name }}" loading="lazy" decoding="async" width="320" height="180">
            </picture>
            @endif
            <span>{{ $category->name }}</span>
            <small>{{ \Illuminate\Support\Str::limit($category->meta_description ?: 'Подборка моделей для офиса и дома.', 88) }}</small>
        </a>
        @endforeach
    </div>
</section>
@endif
