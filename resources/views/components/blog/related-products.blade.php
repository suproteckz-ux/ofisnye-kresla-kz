@props(['products'])

@if($products->count())
<section class="blog-section">
    <div class="blog-section__head">
        <div>
            <h2>Подходящие кресла</h2>
            <p>Модели, которые стоит посмотреть после прочтения статьи.</p>
        </div>
        <a href="{{ url('/ofisnye-kresla') }}">Все товары</a>
    </div>
    <div class="pgrid">
        @foreach($products->take(8) as $product)
            <x-product.card :product="$product" item-list-id="blog_related" item-list-name="Блог — товары" :item-index="$loop->iteration" />
        @endforeach
    </div>
</section>
@endif
