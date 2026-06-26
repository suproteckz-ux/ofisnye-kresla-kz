@php
    $entity    = $seoFilter ?? null;
    $seoTitle  = $entity?->seoTitle() ?? $category->seoTitle();
    $seoDesc   = $entity?->seoDescription() ?? $category->seoDescription();
    $seoH1Text = $entity?->seoH1() ?? $category->seoH1();
@endphp

@extends('layouts.app')
@section('title', $seoTitle)
@section('description', $seoDesc)
@if($noindex ?? false)
@section('noindex', true)
@endif

@section('canonical')
<link rel="canonical" href="{{ $canonical }}">
@if(($currentPage ?? 1) > 1 && $products->previousPageUrl())
    <link rel="prev" href="{{ $products->previousPageUrl() }}">
@endif
@if($products->hasMorePages())
    <link rel="next" href="{{ $products->nextPageUrl() }}">
@endif
@endsection

@section('schema')
@php
$bcItems = [['@type'=>'ListItem','position'=>1,'name'=>'Главная','item'=>url('/')]];
foreach ($breadcrumbs ?? [] as $i => $bc) {
    $bcItems[] = ['@type'=>'ListItem','position'=>$i+2,'name'=>$bc['name'],'item'=>$bc['url']];
}
$schemaBC = ['@context'=>'https://schema.org','@type'=>'BreadcrumbList','itemListElement'=>$bcItems];

$itemListElements = [];
foreach ($products as $index => $product) {
    $item = [
        '@type'    => 'ListItem',
        'position' => (($products->currentPage() - 1) * $products->perPage()) + $index + 1,
        'url'      => $product->url,
        'item'     => [
            '@type' => 'Product',
            'name'  => $product->name,
            'url'   => $product->url,
        ],
    ];

    if (!empty($product->main_image)) {
        $item['item']['image'] = asset('storage/' . $product->main_image);
    }

    if (!empty($product->price)) {
        $item['item']['offers'] = [
            '@type'         => 'Offer',
            'priceCurrency' => 'KZT',
            'price'         => (float) $product->price,
            'availability'  => $product->in_stock ? 'https://schema.org/InStock' : 'https://schema.org/OutOfStock',
            'url'           => $product->url,
        ];
    }

    $itemListElements[] = $item;
}
$schemaItemList = [
    '@context'        => 'https://schema.org',
    '@type'           => 'ItemList',
    'name'            => $seoH1Text,
    'url'             => url()->current(),
    'numberOfItems'   => $products->count(),
    'itemListElement' => $itemListElements,
];

$faqItems = collect($category->faq_array ?? [])
    ->filter(fn($faq) => !empty($faq['question'] ?? null) && !empty($faq['answer'] ?? null))
    ->values();
$schemaFaq = null;
if ($faqItems->isNotEmpty()) {
    $schemaFaq = [
        '@context'   => 'https://schema.org',
        '@type'      => 'FAQPage',
        'mainEntity' => $faqItems->map(fn($faq) => [
            '@type'          => 'Question',
            'name'           => strip_tags($faq['question']),
            'acceptedAnswer' => [
                '@type' => 'Answer',
                'text'  => strip_tags($faq['answer']),
            ],
        ])->all(),
    ];
}
@endphp
<script type="application/ld+json">{!! json_encode($schemaBC, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES) !!}</script>
@if(!empty($itemListElements))
<script type="application/ld+json">{!! json_encode($schemaItemList, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES) !!}</script>
@endif
@if($schemaFaq)
<script type="application/ld+json">{!! json_encode($schemaFaq, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES) !!}</script>
@endif
@endsection

@if(isset($products) && $products->count())
@push('scripts')
<x-analytics.event
    once-key="view_item_list:category_{{ $category->slug }}:{{ request()->fullUrl() }}"
    :payload="[
        'event' => 'view_item_list',
        'ecommerce' => [
            'item_list_id' => 'category_' . $category->slug,
            'item_list_name' => $category->name,
            'items' => \App\Support\Analytics::productItems($products->getCollection()),
        ],
    ]"
/>
@endpush
@endif

@section('breadcrumbs')
<div class="container" style="padding-top:14px">
    <nav aria-label="Хлебные крошки" style="font-size:13px;color:#888">
        <a href="{{ url('/') }}" style="color:#888;text-decoration:none">Главная</a>
        @foreach($breadcrumbs ?? [] as $bc)
            <span style="color:#ccc;margin:0 6px">›</span>
            <a href="{{ $bc['url'] }}" style="color:#888;text-decoration:none">{{ $bc['name'] }}</a>
        @endforeach
    </nav>
</div>
@endsection

@section('content')
<div class="container" style="padding-top:12px;padding-bottom:48px">

    <h1 style="font-size:clamp(1.5rem,3vw,2rem);font-weight:800;color:#111;margin-bottom:8px">
        {{ $seoH1Text }}
    </h1>

    @if($category->seo_text_top)
    <div class="prose" style="margin:10px 0 20px;max-width:900px">
        {!! $category->seo_text_top !!}
    </div>
    @endif

    {{-- Подкатегории-пилюли --}}
    @if(!($parent ?? null) && ($category->children?->count() ?? 0) > 0)
    <div style="display:flex;flex-wrap:wrap;gap:8px;margin:14px 0 20px">
        @foreach($category->children as $child)
        <a href="{{ $child->url }}"
           style="display:inline-flex;align-items:center;padding:7px 16px;background:#fff;
                  border:1px solid #eee;border-radius:99px;font-size:13px;font-weight:500;
                  color:#444;text-decoration:none;white-space:nowrap;transition:border-color .2s,color .2s"
           onmouseover="this.style.borderColor='#ff8a00';this.style.color='#ff8a00'"
           onmouseout="this.style.borderColor='#eee';this.style.color='#444'">
            {{ $child->name }}
        </a>
        @endforeach
    </div>
    @endif

    {{-- Счётчик + сортировка --}}
    <div style="display:flex;align-items:center;justify-content:space-between;
                gap:12px;margin-bottom:20px;flex-wrap:wrap">
        <p style="font-size:14px;color:#666">
            {{ $products->total() }} {{ trans_choice('товар|товара|товаров', $products->total()) }}
        </p>
        <select onchange="window.location=this.value"
                style="padding:8px 14px;border:1px solid #eee;border-radius:8px;
                       font-size:13px;background:#fff;cursor:pointer;outline:none;color:#111">
            @foreach(['default'=>'По популярности','price_asc'=>'Цена ↑','price_desc'=>'Цена ↓','new'=>'Сначала новые'] as $val=>$label)
            <option value="{{ request()->fullUrlWithQuery(['sort'=>$val,'page'=>null]) }}"
                    {{ ($sort??'default')===$val?'selected':'' }}>{{ $label }}</option>
            @endforeach
        </select>
    </div>

    {{-- Товары --}}
    @if($products->count())
    <div class="pgrid">
        @foreach($products as $index => $product)
            @include('components.product.card', [
                'product' => $product,
                'itemListId' => 'category_' . $category->slug,
                'itemListName' => $category->name,
                'itemIndex' => (($products->currentPage() - 1) * $products->perPage()) + $index + 1,
            ])
        @endforeach
    </div>
    <div style="margin-top:32px;display:flex;justify-content:center">{{ $products->links() }}</div>
    @else
    <div style="text-align:center;padding:60px 0">
        <p style="color:#666;font-size:15px;margin-bottom:16px">Товары не найдены</p>
        <a href="{{ url()->current() }}" class="btn-orange">Вся категория</a>
    </div>
    @endif

    <section style="margin-top:48px">
        <h2 style="font-size:22px;font-weight:800;color:#111;margin-bottom:18px">Почему выбирают нас</h2>
        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:12px">
            @foreach([
                ['title'=>'Доставка по Алматы','text'=>'Быстро доставляем офисные кресла и стулья по городу.'],
                ['title'=>'Доставка по Казахстану','text'=>'Отправляем заказы в Астану, Шымкент и другие города.'],
                ['title'=>'Помощь в подборе','text'=>'Подберём модель под задачу, бюджет и формат помещения.'],
                ['title'=>'Гарантия на товары','text'=>'Предоставляем гарантию на механизмы, основание и комплектующие.'],
                ['title'=>'Заказ через WhatsApp','text'=>'Можно быстро уточнить наличие, цену и оформить заказ.'],
            ] as $advantage)
            <div style="border:1px solid #eee;border-radius:14px;padding:16px;background:#fff">
                <div style="font-weight:700;color:#111;margin-bottom:6px">{{ $advantage['title'] }}</div>
                <div style="font-size:14px;color:#666;line-height:1.6">{{ $advantage['text'] }}</div>
            </div>
            @endforeach
        </div>
    </section>

    @if($category->seo_text_bottom)
    <div class="prose" style="margin-top:48px;max-width:900px">{!! $category->seo_text_bottom !!}</div>
    @endif

    @php $categoryFaq = $category->faq_array ?? []; @endphp
    @if(!empty($categoryFaq))
    <div style="margin-top:40px;max-width:760px" x-data="{open:null}">
        <h2 style="font-size:20px;font-weight:700;color:#111;margin-bottom:16px">Частые вопросы</h2>
        @foreach($categoryFaq as $i=>$faq)
        <div class="faq-item">
            <button class="faq-q" @click="open===`f{{ $i }}`?open=null:open=`f{{ $i }}`">
                {{ $faq['question']??'' }}
                <svg style="flex-shrink:0;width:14px;height:14px;color:#aaa;transition:transform .2s"
                     :style="open===`f{{ $i }}`?'transform:rotate(180deg)':''"
                     fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path d="M6 9l6 6 6-6"/>
                </svg>
            </button>
            <div x-show="open===`f{{ $i }}`" class="faq-a">{{ $faq['answer']??'' }}</div>
        </div>
        @endforeach
    </div>
    @endif
</div>
@endsection
