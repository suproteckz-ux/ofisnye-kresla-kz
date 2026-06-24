@php
    $seoEntity = $page ?? $filter ?? null;
    $seoTitle = $seoEntity?->seoTitle() ?? config('app.name');
    $seoDescription = $seoEntity?->seoDescription() ?? '';
    $seoH1 = $seoEntity?->seoH1() ?? ($seoEntity->title ?? '');
    $canonicalUrl = isset($page) ? $page->url : ($canonical ?? url()->current());
    $categories = isset($page) ? ($page->categories ?? collect()) : collect();
    $rawLandingProducts = $relatedProducts ?? $products ?? collect();
    $landingProducts = $rawLandingProducts instanceof \Illuminate\Contracts\Pagination\Paginator
        ? collect($rawLandingProducts->items())
        : collect($rawLandingProducts);
    $seoText = isset($page) ? $page->seo_text : ($filter->seo_text ?? null);
    $faqItems = isset($page) ? collect($page->faq_array ?? [])->filter(fn ($faq) => !empty($faq['question'] ?? null) && !empty($faq['answer'] ?? null))->values() : collect();
    $heroPath = $heroImage ?? null;
    $heroSubtitle = isset($page) ? ($page->hero_subtitle ?: ($page->meta_description ?: $seoDescription)) : $seoDescription;
    $ctaTitle = isset($page) ? ($page->cta_title ?: 'Поможем подобрать офисное кресло') : 'Поможем подобрать офисное кресло';
    $ctaText = isset($page) ? ($page->cta_text ?: 'Напишите нам в WhatsApp, подскажем по наличию, цене, доставке и документам для юрлиц.') : 'Напишите нам в WhatsApp, подскажем по наличию, цене, доставке и документам для юрлиц.';
    $ctaButtonText = isset($page) ? ($page->cta_button_text ?: 'Написать в WhatsApp') : 'Написать в WhatsApp';
    $ctaButtonUrl = isset($page) ? $page->cta_button_url : null;
    $whatsappRaw = \App\Services\CacheService::setting('whatsapp', '');
    $whatsappPhone = preg_replace('/\D+/', '', (string) $whatsappRaw);
    $whatsappMessage = urlencode('Здравствуйте! Хочу подобрать офисное кресло со страницы ' . $seoH1);
    $whatsappUrl = $whatsappPhone ? "https://wa.me/{$whatsappPhone}?text={$whatsappMessage}" : null;
    $finalCtaUrl = $ctaButtonUrl ?: $whatsappUrl;
    $primaryButtonText = isset($page) ? ($page->hero_button_text ?: 'Смотреть товары') : 'Смотреть товары';
    $primaryButtonUrl = isset($page) && $page->hero_button_url
        ? $page->hero_button_url
        : (($landingProducts->count() ?? 0) ? '#seo-products' : ($seoText ? '#seo-text' : ($finalCtaUrl ?: url('/contacts'))));

    $faqSchema = null;
    if ($faqItems->isNotEmpty()) {
        $faqSchema = [
            '@context' => 'https://schema.org',
            '@type' => 'FAQPage',
            'mainEntity' => $faqItems->map(fn ($faq) => [
                '@type' => 'Question',
                'name' => strip_tags($faq['question']),
                'acceptedAnswer' => [
                    '@type' => 'Answer',
                    'text' => strip_tags($faq['answer']),
                ],
            ])->all(),
        ];
    }

    $itemListElements = collect($landingProducts)->take(8)->values()->map(function ($product, $index) {
        $item = [
            '@type' => 'ListItem',
            'position' => $index + 1,
            'url' => $product->url,
            'item' => [
                '@type' => 'Product',
                'name' => $product->name,
                'url' => $product->url,
            ],
        ];

        if (!empty($product->main_image)) {
            $item['item']['image'] = asset('storage/' . $product->main_image);
        }

        if (!empty($product->price)) {
            $item['item']['offers'] = [
                '@type' => 'Offer',
                'priceCurrency' => 'KZT',
                'price' => (float) $product->price,
                'availability' => $product->in_stock ? 'https://schema.org/InStock' : 'https://schema.org/OutOfStock',
                'url' => $product->url,
            ];
        }

        return $item;
    })->all();
@endphp

@extends('layouts.app')

@section('title', $seoTitle)
@section('description', $seoDescription)

@section('canonical')
<link rel="canonical" href="{{ $canonicalUrl }}">
@endsection

@section('breadcrumbs')
<x-ui.breadcrumbs :items="$breadcrumbs ?? []"/>
@endsection

@section('schema')
<x-schema.breadcrumbs :items="$breadcrumbs ?? []"/>
@if(!empty($itemListElements))
<script type="application/ld+json">{!! json_encode([
    '@context' => 'https://schema.org',
    '@type' => 'ItemList',
    'name' => $seoH1,
    'url' => $canonicalUrl,
    'numberOfItems' => count($itemListElements),
    'itemListElement' => $itemListElements,
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!}</script>
@endif
@if($faqSchema)
<script type="application/ld+json">{!! json_encode($faqSchema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!}</script>
@endif
@endsection

@section('content')
<main class="seo-landing">
    <section class="seo-hero {{ $heroPath ? '' : 'seo-hero--text-only' }}">
        <div class="seo-hero__content">
            <h1>{{ $seoH1 }}</h1>
            @if($heroSubtitle)
            <p>{{ $heroSubtitle }}</p>
            @endif
            <div class="seo-hero__actions">
                <a class="seo-btn seo-btn--orange" href="{{ $primaryButtonUrl }}">{{ $primaryButtonText }}</a>
                @if($whatsappUrl)
                <a class="seo-btn seo-btn--whatsapp" href="{{ $whatsappUrl }}" target="_blank" rel="noopener">WhatsApp</a>
                @endif
            </div>
        </div>

        @if($heroPath)
        <div class="seo-hero__image">
            <img src="{{ asset('storage/' . $heroPath) }}" alt="{{ $seoH1 }}" loading="eager">
        </div>
        @endif
    </section>

    <section class="seo-benefits" aria-label="Преимущества">
        @foreach([
            ['title' => 'Доставка по Алматы', 'text' => 'Привезем кресла по городу и согласуем удобное время.'],
            ['title' => 'Помощь в подборе', 'text' => 'Подскажем модель под рост, задачи, бюджет и формат офиса.'],
            ['title' => 'Гарантия', 'text' => 'Работаем с проверенными креслами и помогаем после покупки.'],
            ['title' => 'Для юрлиц', 'text' => 'Подготовим документы для компании и офисной закупки.'],
            ['title' => 'Заказ через WhatsApp', 'text' => 'Быстро уточним наличие, цену, доставку и оформление.'],
        ] as $benefit)
        <article class="seo-benefit">
            <div class="seo-benefit__mark"></div>
            <h2>{{ $benefit['title'] }}</h2>
            <p>{{ $benefit['text'] }}</p>
        </article>
        @endforeach
    </section>

    @if($categories->isNotEmpty())
    <section class="seo-section">
        <div class="seo-section__head">
            <h2>Популярные категории</h2>
        </div>
        <div class="seo-category-grid">
            @foreach($categories as $category)
            @php
                $fallbackProduct = $landingProducts->first(fn ($product) => (int) ($product->category_id ?? 0) === (int) $category->id && !empty($product->main_image));
                $categoryImage = $category->image ?: ($fallbackProduct->main_image ?? null);
                $categoryDescription = strip_tags($category->meta_description ?: $category->seo_text_top ?: 'Подборка моделей для офиса и дома.');
            @endphp
            <article class="seo-category-card">
                @if(!empty($categoryImage))
                <a class="seo-category-card__image" href="{{ $category->url }}" aria-label="{{ $category->name }}">
                    <img src="{{ asset('storage/' . $categoryImage) }}" alt="{{ $category->name }}" loading="lazy">
                </a>
                @endif
                <div class="seo-category-card__body">
                    <h3>{{ $category->name }}</h3>
                    <p>{{ $categoryDescription }}</p>
                    <a class="seo-category-card__button" href="{{ $category->url }}">Смотреть</a>
                </div>
            </article>
            @endforeach
        </div>
    </section>
    @endif

    @if(($landingProducts->count() ?? 0) > 0)
    <section class="seo-section" id="seo-products">
        <div class="seo-section__head">
            <h2>Популярные товары</h2>
        </div>
        <div class="seo-products-grid">
            @foreach($landingProducts->take(8) as $product)
                <x-product.card :product="$product"/>
            @endforeach
        </div>
    </section>
    @endif

    @if(!empty($seoText))
    <section class="seo-text" id="seo-text">
        {!! $seoText !!}
    </section>
    @endif

    @if($finalCtaUrl)
    <section class="seo-cta">
        <div>
            <h2>{{ $ctaTitle }}</h2>
            <p>{{ $ctaText }}</p>
        </div>
        <a class="seo-btn {{ $whatsappUrl && $finalCtaUrl === $whatsappUrl ? 'seo-btn--whatsapp' : 'seo-btn--orange' }}"
           href="{{ $finalCtaUrl }}"
           @if(str_starts_with($finalCtaUrl, 'http')) target="_blank" rel="noopener" @endif>
            {{ $ctaButtonText }}
        </a>
    </section>
    @endif

    @if($faqItems->isNotEmpty())
    <section class="seo-faq" x-data="{open:null}">
        <h2>Частые вопросы</h2>
        @foreach($faqItems as $i => $faq)
        <div class="faq-item">
            <button class="faq-q" type="button" @click="open===`seo{{ $i }}`?open=null:open=`seo{{ $i }}`">
                {{ $faq['question'] ?? '' }}
                <svg style="flex-shrink:0;width:14px;height:14px;color:#aaa;transition:transform .2s"
                     :style="open===`seo{{ $i }}`?'transform:rotate(180deg)':''"
                     fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path d="M6 9l6 6 6-6"/>
                </svg>
            </button>
            <div x-show="open===`seo{{ $i }}`" class="faq-a">{{ $faq['answer'] ?? '' }}</div>
        </div>
        @endforeach
    </section>
    @endif
</main>

<style>
.seo-landing{max-width:1180px;margin:0 auto;padding:18px 16px 56px;color:#111}
.seo-hero{display:grid;grid-template-columns:minmax(0,1.05fr) minmax(320px,.95fr);gap:28px;align-items:center;margin:4px 0 22px;padding:28px;border:1px solid #eee;border-radius:18px;background:#fff;box-shadow:0 16px 42px rgba(17,17,17,.06)}
.seo-hero--text-only{grid-template-columns:1fr}
.seo-hero--text-only .seo-hero__content{max-width:760px}
.seo-hero__content h1{font-size:34px;line-height:1.12;font-weight:850;margin:0 0 14px;color:#111;letter-spacing:0}
.seo-hero__content p{font-size:16px;line-height:1.7;color:#57534e;margin:0 0 22px;max-width:640px}
.seo-hero__actions{display:flex;flex-wrap:wrap;gap:10px}
.seo-hero__image{height:330px;border-radius:18px;background:#fafaf9;overflow:hidden}
.seo-hero__image img{width:100%;height:100%;object-fit:contain;padding:18px}
.seo-btn{display:inline-flex;align-items:center;justify-content:center;min-height:44px;padding:11px 18px;border-radius:12px;font-size:14px;font-weight:800;text-decoration:none;transition:transform .2s,box-shadow .2s,background .2s}
.seo-btn:hover{transform:translateY(-1px);box-shadow:0 10px 22px rgba(17,17,17,.1)}
.seo-btn--orange{background:#ff8a00;color:#fff}
.seo-btn--orange:hover{background:#ea7a00;color:#fff}
.seo-btn--whatsapp{background:#22c55e;color:#fff}
.seo-btn--whatsapp:hover{background:#16a34a;color:#fff}
.seo-benefits{display:grid;grid-template-columns:repeat(5,minmax(0,1fr));gap:12px;margin:22px 0 34px}
.seo-benefit{border:1px solid #eee;border-radius:16px;background:#fff;padding:16px;box-shadow:0 8px 24px rgba(17,17,17,.04)}
.seo-benefit__mark{width:30px;height:4px;border-radius:99px;background:#ff8a00;margin-bottom:12px}
.seo-benefit h2{font-size:15px;line-height:1.35;font-weight:800;margin:0 0 7px;color:#111}
.seo-benefit p{font-size:13px;line-height:1.55;color:#666;margin:0}
.seo-section{margin-top:36px}
.seo-section__head{display:flex;align-items:end;justify-content:space-between;gap:16px;margin-bottom:16px}
.seo-section__head h2,.seo-faq h2,.seo-cta h2{font-size:24px;line-height:1.25;font-weight:850;color:#111;margin:0}
.seo-category-grid{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:18px}
.seo-category-card{display:flex;flex-direction:column;min-height:100%;border:1px solid #eee;border-radius:18px;background:#fff;overflow:hidden;color:#111;text-decoration:none;box-shadow:0 8px 24px rgba(17,17,17,.04);transition:border-color .2s,box-shadow .2s,transform .2s}
.seo-category-card:hover{border-color:#ff8a00;box-shadow:0 16px 34px rgba(17,17,17,.1);transform:translateY(-2px)}
.seo-category-card__image{display:block;height:165px;background:#fff;overflow:hidden}
.seo-category-card__image img{width:100%;height:100%;object-fit:contain;padding:12px}
.seo-category-card__body{display:flex;flex-direction:column;align-items:flex-start;gap:10px;flex:1;min-width:0;padding:18px 20px 20px}
.seo-category-card h3{font-size:19px;line-height:1.3;font-weight:700;margin:0;color:#111}
.seo-category-card p{display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden;min-height:44px;margin:0;color:#666;font-size:14px;line-height:1.55}
.seo-category-card__button{display:inline-flex;align-items:center;justify-content:center;margin-top:auto;min-height:42px;padding:10px 20px;border-radius:10px;background:#ff8a00;color:#fff;font-size:14px;font-weight:800;text-decoration:none;transition:background .2s,box-shadow .2s}
.seo-category-card__button:hover{background:#ea7a00;color:#fff;box-shadow:0 10px 18px rgba(255,138,0,.24)}
.seo-products-grid{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:16px}
.seo-text{max-width:900px;margin-top:42px;color:#444;font-size:16px;line-height:1.78}
.seo-text h2{font-size:24px;line-height:1.25;font-weight:850;color:#111;margin:34px 0 12px}
.seo-text h3{font-size:20px;line-height:1.3;font-weight:800;color:#111;margin:26px 0 10px}
.seo-text p{margin:0 0 16px}
.seo-text ul,.seo-text ol{padding-left:22px;margin:0 0 18px}
.seo-text a{color:#d97706;text-decoration:underline;text-decoration-thickness:1px;text-underline-offset:3px}
.seo-cta{display:flex;align-items:center;justify-content:space-between;gap:22px;margin-top:42px;padding:24px;border-radius:18px;background:#111;color:#fff;box-shadow:0 16px 38px rgba(17,17,17,.14)}
.seo-cta p{margin:8px 0 0;max-width:700px;font-size:15px;line-height:1.65;color:#e7e5e4}
.seo-cta h2{color:#fff}
.seo-faq{max-width:820px;margin-top:42px}
.seo-faq h2{margin-bottom:12px}
@media (max-width: 980px){
    .seo-hero{grid-template-columns:1fr;padding:20px}
    .seo-hero__image{height:280px}
    .seo-benefits{grid-template-columns:repeat(2,minmax(0,1fr))}
    .seo-category-grid{grid-template-columns:repeat(2,minmax(0,1fr))}
    .seo-products-grid{grid-template-columns:repeat(3,minmax(0,1fr))}
}
@media (max-width: 640px){
    .seo-landing{padding:12px 12px 40px}
    .seo-hero{gap:18px;border-radius:16px;padding:18px}
    .seo-hero__content h1{font-size:25px}
    .seo-hero__content p{font-size:14px}
    .seo-hero__image{height:230px}
    .seo-btn{width:100%}
    .seo-benefits{grid-template-columns:1fr;gap:10px;margin-bottom:28px}
    .seo-category-grid{grid-template-columns:1fr;gap:12px}
    .seo-category-card__image{height:155px}
    .seo-category-card__body{padding:17px 18px 18px}
    .seo-category-card h3{font-size:18px}
    .seo-products-grid{grid-template-columns:repeat(2,minmax(0,1fr));gap:10px}
    .seo-section__head h2,.seo-faq h2,.seo-cta h2,.seo-text h2{font-size:21px}
    .seo-text{font-size:15px;line-height:1.72}
    .seo-cta{display:block;padding:20px}
    .seo-cta .seo-btn{margin-top:16px}
}
</style>
@endsection
