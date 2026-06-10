@extends('layouts.app')

@section('title', 'Офисные кресла в Алматы — купить с доставкой | ' . config('app.name'))
@section('description', 'Офисные кресла в Алматы: 79 моделей для руководителей, сотрудников, посетителей и учебных пространств. Доставка по Алматы и Казахстану, гарантия от 12 месяцев.')
@section('canonical')<link rel="canonical" href="{{ url('/') }}">@endsection

@php
    $wa = \App\Services\CacheService::setting('whatsapp', '');
    $homeFaq = [
        ['Как выбрать офисное кресло?', 'Ориентируйтесь на продолжительность работы, поддержку поясницы, регулировку высоты, подлокотники и допустимую нагрузку. Напишите нам в WhatsApp — поможем подобрать модель под рост, задачи и бюджет.'],
        ['Сколько стоит доставка в Алматы?', 'Стоимость зависит от адреса и условий заказа. Для большинства заказов по Алматы доставка занимает 1–2 дня; точную стоимость менеджер подтвердит при оформлении.'],
        ['Есть ли доставка по Казахстану?', 'Да, отправляем офисные кресла в города Казахстана транспортными компаниями. Срок и стоимость зависят от региона.'],
        ['Есть ли сборка кресел?', 'Да, возможность и стоимость сборки можно согласовать с менеджером при оформлении заказа.'],
        ['Можно ли оплатить заказ через Kaspi?', 'Да, доступны оплата через Kaspi, наличными и банковской картой. Условия оплаты менеджер подтвердит перед доставкой.'],
        ['Какая гарантия на кресла?', 'На большинство моделей действует гарантия от 12 месяцев. Точный срок указан в характеристиках товара и подтверждается при заказе.'],
        ['Работаете ли с юридическими лицами?', 'Да, работаем с организациями и ИП, заключаем договоры и предоставляем закрывающие документы.'],
        ['Вы являетесь плательщиками НДС?', 'Да, компания работает с НДС. Для юридических лиц предоставляется полный пакет документов.'],
    ];

    $websiteSchema = [
        '@context' => 'https://schema.org',
        '@type' => 'WebSite',
        'name' => config('app.name'),
        'url' => url('/'),
        'potentialAction' => [
            '@type' => 'SearchAction',
            'target' => url('/search') . '?q={search_term_string}',
            'query-input' => 'required name=search_term_string',
        ],
    ];
    $faqSchema = [
        '@context' => 'https://schema.org',
        '@type' => 'FAQPage',
        'mainEntity' => collect($homeFaq)->map(fn ($item) => [
            '@type' => 'Question',
            'name' => $item[0],
            'acceptedAnswer' => [
                '@type' => 'Answer',
                'text' => $item[1],
            ],
        ])->all(),
    ];
    $legalHref = $wa
        ? 'https://wa.me/' . $wa . '?text=' . urlencode('Здравствуйте! Нужна информация по работе с юридическими лицами и НДС.')
        : url('/#contacts');
@endphp

@section('schema')
<script type="application/ld+json">{!! json_encode($websiteSchema, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES) !!}</script>
<script type="application/ld+json">{!! json_encode($faqSchema, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES) !!}</script>
@endsection

@section('content')
<section class="home-hero">
    <div class="container">
        <div class="home-hero-card">
            <img class="home-hero-image"
                 src="{{ asset('images/home-office-chair.webp') }}"
                 alt="Эргономичное офисное кресло в современном офисе Алматы"
                 width="1672"
                 height="941"
                 loading="eager"
                 fetchpriority="high">
            <div class="home-hero-overlay"></div>

            <div class="home-hero-copy">
                <div class="home-eyebrow">
                    <span></span>
                    Более {{ $totalProducts ?? 79 }} моделей в наличии
                </div>
                <h1>Офисные кресла<br>в Алматы</h1>
                <p>Кресла для руководителей, эргономичные, компьютерные и игровые. Доставка по Алматы и Казахстану.</p>
                <div class="home-hero-actions">
                    <a href="{{ route('catalog') }}" class="btn-orange">
                        Перейти в каталог
                        <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><path d="M5 12h14M13 5l7 7-7 7"/></svg>
                    </a>
                    @if($wa)
                    <a href="https://wa.me/{{ $wa }}?text={{ urlencode('Здравствуйте! Помогите подобрать офисное кресло.') }}"
                       target="_blank" rel="noopener" class="home-wa-button">
                        <svg width="18" height="18" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path d="M17.47 14.38c-.3-.15-1.76-.87-2.03-.97-.27-.1-.47-.15-.67.15-.2.3-.77.97-.94 1.16-.17.2-.35.22-.64.08-.3-.15-1.26-.46-2.39-1.48-.88-.79-1.48-1.76-1.65-2.06-.17-.3-.02-.46.13-.61.13-.13.3-.35.45-.52.15-.17.2-.3.3-.5.1-.2.05-.37-.03-.52-.07-.15-.67-1.61-.92-2.21-.24-.58-.49-.5-.67-.51h-.57c-.2 0-.52.07-.79.37-.27.3-1.04 1.02-1.04 2.48s1.07 2.88 1.21 3.07c.15.2 2.1 3.2 5.08 4.49.71.31 1.26.49 1.69.63.71.23 1.36.2 1.87.12.57-.09 1.76-.72 2.01-1.41.25-.69.25-1.29.17-1.41-.07-.13-.27-.2-.57-.35z"/></svg>
                        Консультация в WhatsApp
                    </a>
                    @endif
                </div>
            </div>

            <div class="home-hero-benefits" id="delivery-payment">
                @foreach([
                    ['delivery', 'Доставка по Алматы', '1–2 дня'],
                    ['shield', 'Гарантия', 'от 12 месяцев'],
                    ['return', 'Возврат', '14 дней'],
                    ['payment', 'Оплата', 'Наличные, Kaspi, карта'],
                    ['business', 'Работаем с юр. лицами', 'Плательщики НДС'],
                ] as [$icon, $title, $description])
                <div class="home-mini-benefit">
                    <span class="home-icon home-icon-{{ $icon }}" aria-hidden="true">
                        @if($icon === 'delivery')
                        <svg viewBox="0 0 24 24"><path d="M3 6h11v10H3zM14 9h4l3 3v4h-7zM7 20a2 2 0 100-4 2 2 0 000 4zm10 0a2 2 0 100-4 2 2 0 000 4z"/></svg>
                        @elseif($icon === 'shield')
                        <svg viewBox="0 0 24 24"><path d="M12 3l8 4v5c0 5-3.4 8-8 9-4.6-1-8-4-8-9V7l8-4z"/></svg>
                        @elseif($icon === 'return')
                        <svg viewBox="0 0 24 24"><path d="M4 7v5h5M5 12a7 7 0 111.8 4.7"/></svg>
                        @elseif($icon === 'payment')
                        <svg viewBox="0 0 24 24"><path d="M3 6h18v12H3zM3 10h18M7 15h4"/></svg>
                        @else
                        <svg viewBox="0 0 24 24"><path d="M4 21V5h10v16M14 9h6v12M8 9h2M8 13h2M8 17h2M17 13h1M17 17h1M2 21h20"/></svg>
                        @endif
                    </span>
                    <span><strong>{{ $title }}</strong><small>{{ $description }}</small></span>
                </div>
                @endforeach
            </div>
        </div>
    </div>
</section>

@if(($hits ?? collect())->isNotEmpty())
<section class="home-section" id="sales">
    <div class="container">
        <div class="home-section-heading">
            <h2>Хиты продаж</h2>
            <a href="{{ route('catalog') }}">Смотреть все <span>→</span></a>
        </div>
        <div class="home-products-grid">
            @foreach($hits->take(6) as $product)
                @include('components.product.card', ['product' => $product])
            @endforeach
        </div>
    </div>
</section>
@endif

@if(($categories ?? collect())->isNotEmpty())
<section class="home-section home-categories-section">
    <div class="container">
        <div class="home-section-heading">
            <h2>Популярные категории</h2>
            <a href="{{ route('catalog') }}">Смотреть все <span>→</span></a>
        </div>
        <div class="home-category-grid">
            @foreach($categories as $category)
            @php
                $previewProduct = $category->products->first();
                $modelsCount = (int) $category->active_products_count;
                $modelsWord = ($modelsCount % 10 === 1 && $modelsCount % 100 !== 11)
                    ? 'модель'
                    : (in_array($modelsCount % 10, [2, 3, 4], true) && !in_array($modelsCount % 100, [12, 13, 14], true)
                        ? 'модели'
                        : 'моделей');
            @endphp
            <a href="{{ $category->url }}" class="home-category-card">
                <div class="home-category-image">
                    <img src="{{ $previewProduct?->main_image ? asset('storage/' . $previewProduct->main_image) : asset('img/no-photo.svg') }}"
                         alt="{{ $previewProduct?->main_image_alt ?: $category->name }}"
                         width="360" height="260" loading="lazy"
                         onerror="this.onerror=null;this.src='{{ asset('img/no-photo.svg') }}'">
                </div>
                <div class="home-category-info">
                    <strong>{{ $category->name }}</strong>
                    <span>{{ $modelsCount }} {{ $modelsWord }}</span>
                </div>
            </a>
            @endforeach
        </div>
    </div>
</section>
@endif

<section class="home-section">
    <div class="container">
        <div class="home-why-card">
            <h2>Почему выбирают нас</h2>
            <div class="home-why-grid">
                @foreach([
                    ['quality', 'Более ' . ($totalProducts ?? 79) . ' моделей в наличии', 'Всегда актуальный ассортимент'],
                    ['delivery', 'Быстрая доставка по Алматы', 'Доставка 1–2 дня'],
                    ['shield', 'Гарантия качества от 12 месяцев', 'Официальная гарантия на кресла'],
                    ['payment', 'Удобная оплата', 'Наличные, Kaspi, банковская карта'],
                    ['return', 'Лёгкий возврат и обмен', 'Возврат в течение 14 дней'],
                    ['business', 'Работаем с юр. лицами', 'Плательщики НДС'],
                ] as [$icon, $title, $description])
                <div class="home-why-item">
                    <span class="home-why-icon" aria-hidden="true">
                        @if($icon === 'quality')
                        <svg viewBox="0 0 24 24"><path d="M12 3l2 2.2 3-.3.9 2.9 2.7 1.5-1.2 2.8 1.2 2.8-2.7 1.5-.9 2.9-3-.3L12 21l-2-2.2-3 .3-.9-2.9-2.7-1.5 1.2-2.8-1.2-2.8 2.7-1.5L7 4.9l3 .3L12 3zM9 12l2 2 4-4"/></svg>
                        @elseif($icon === 'delivery')
                        <svg viewBox="0 0 24 24"><path d="M3 6h11v10H3zM14 9h4l3 3v4h-7zM7 20a2 2 0 100-4 2 2 0 000 4zm10 0a2 2 0 100-4 2 2 0 000 4z"/></svg>
                        @elseif($icon === 'shield')
                        <svg viewBox="0 0 24 24"><path d="M12 3l8 4v5c0 5-3.4 8-8 9-4.6-1-8-4-8-9V7l8-4z"/></svg>
                        @elseif($icon === 'payment')
                        <svg viewBox="0 0 24 24"><path d="M3 6h18v12H3zM3 10h18M7 15h4"/></svg>
                        @elseif($icon === 'return')
                        <svg viewBox="0 0 24 24"><path d="M4 7v5h5M5 12a7 7 0 111.8 4.7"/></svg>
                        @else
                        <svg viewBox="0 0 24 24"><path d="M4 21V5h10v16M14 9h6v12M8 9h2M8 13h2M8 17h2M17 13h1M17 17h1M2 21h20"/></svg>
                        @endif
                    </span>
                    <span><strong>{{ $title }}</strong><small>{{ $description }}</small></span>
                </div>
                @endforeach
            </div>

            <div class="home-legal-banner" id="contacts">
                <div>
                    <strong>Работаем с организациями и ИП</strong>
                    <span>Плательщики НДС. Заключаем договоры и предоставляем полный пакет документов.</span>
                </div>
                <a href="{{ $legalHref }}" @if($wa) target="_blank" rel="noopener" @endif>
                    Подробнее для юр. лиц <span>→</span>
                </a>
            </div>
        </div>
    </div>
</section>

<section class="home-section home-faq-section">
    <div class="container">
        <div class="home-section-heading">
            <h2>Часто задаваемые вопросы</h2>
            <a href="{{ url('/#contacts') }}">Связаться с нами <span>→</span></a>
        </div>
        <div class="home-faq-grid">
            @foreach($homeFaq as [$question, $answer])
            <details class="home-faq-item">
                <summary>
                    {{ $question }}
                    <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><path d="M6 9l6 6 6-6"/></svg>
                </summary>
                <p>{{ $answer }}</p>
            </details>
            @endforeach
        </div>
    </div>
</section>

<style>
.home-hero{padding:14px 0 0}
.home-hero-card{position:relative;min-height:520px;border-radius:18px;overflow:hidden;background:#f7f3ef}
.home-hero-image{position:absolute;inset:0;width:100%;height:100%;object-fit:cover;object-position:center}
.home-hero-overlay{position:absolute;inset:0;background:linear-gradient(90deg,rgba(255,255,255,.99) 0%,rgba(255,255,255,.94) 27%,rgba(255,255,255,.5) 49%,rgba(255,255,255,0) 70%)}
.home-hero-copy{position:relative;z-index:2;width:min(48%,530px);padding:58px 42px 135px}
.home-eyebrow{display:flex;align-items:center;gap:8px;color:#f97316;font-size:13px;font-weight:700;margin-bottom:18px}
.home-eyebrow span{width:8px;height:8px;border-radius:50%;background:#f97316;flex:0 0 auto}
.home-hero h1{font-size:clamp(40px,5vw,64px);font-weight:800;line-height:1.04;letter-spacing:-.035em;margin-bottom:18px;color:#111}
.home-hero-copy>p{max-width:420px;color:#57534e;font-size:15px;line-height:1.65;margin-bottom:26px}
.home-hero-actions{display:flex;align-items:center;flex-wrap:wrap;gap:12px}
.home-wa-button{display:inline-flex;align-items:center;gap:8px;padding:12px 18px;background:#fff;border:1px solid #d6d3d1;border-radius:10px;color:#1c1917;font-size:14px;font-weight:600;box-shadow:0 4px 16px rgba(0,0,0,.05)}
.home-wa-button:hover{border-color:#22c55e;color:#16a34a}
.home-hero-benefits{position:absolute;z-index:3;left:30px;right:30px;bottom:22px;display:grid;grid-template-columns:repeat(5,minmax(0,1fr));gap:10px}
.home-mini-benefit{min-height:66px;display:flex;align-items:center;gap:10px;padding:11px 12px;background:rgba(255,255,255,.92);border:1px solid rgba(255,255,255,.8);border-radius:11px;box-shadow:0 8px 24px rgba(28,25,23,.09);backdrop-filter:blur(8px)}
.home-mini-benefit strong,.home-mini-benefit small{display:block}
.home-mini-benefit strong{font-size:11px;line-height:1.3;color:#1c1917}
.home-mini-benefit small{font-size:10px;color:#78716c;margin-top:3px}
.home-icon{width:34px;height:34px;border-radius:9px;display:grid;place-items:center;flex:0 0 auto;background:#fff7ed;color:#f97316}
.home-icon-payment{background:#ecfdf5;color:#10b981}.home-icon-return{background:#eff6ff;color:#3b82f6}.home-icon-business{background:#eff6ff;color:#2563eb}
.home-icon svg,.home-why-icon svg{width:21px;height:21px;fill:none;stroke:currentColor;stroke-width:1.8;stroke-linecap:round;stroke-linejoin:round}
.home-section{padding:34px 0}
.home-section-heading{display:flex;align-items:center;justify-content:space-between;gap:18px;margin-bottom:18px}
.home-section-heading h2,.home-why-card>h2{font-size:24px;line-height:1.2;font-weight:800;color:#111}
.home-section-heading a{font-size:13px;color:#44403c;white-space:nowrap}.home-section-heading a:hover{color:#f97316}
.home-products-grid{display:grid;grid-template-columns:repeat(6,minmax(0,1fr));gap:14px}
.home-products-grid .card-img-wrap{height:190px!important}
.home-category-grid{display:grid;grid-template-columns:repeat(6,minmax(0,1fr));gap:14px}
.home-category-card{display:flex;flex-direction:column;overflow:hidden;border:1px solid #e7e5e4;border-radius:14px;background:#fff;transition:transform .2s,box-shadow .2s}
.home-category-card:hover{transform:translateY(-2px);box-shadow:0 10px 30px rgba(28,25,23,.08)}
.home-category-image{height:180px;background:#f8f8f7;overflow:hidden}
.home-category-image img{width:100%;height:100%;object-fit:contain;padding:10px}
.home-category-info{display:flex;flex-direction:column;gap:7px;padding:14px;min-height:88px}
.home-category-info strong{font-size:14px;line-height:1.3;color:#1c1917}
.home-category-info span{font-size:12px;color:#78716c}
.home-why-card{padding:24px;border:1px solid #e7e5e4;border-radius:16px;background:#fff}
.home-why-card>h2{margin-bottom:22px}
.home-why-grid{display:grid;grid-template-columns:repeat(6,minmax(0,1fr));gap:20px}
.home-why-item{display:flex;align-items:flex-start;gap:11px}
.home-why-icon{width:39px;height:39px;display:grid;place-items:center;color:#f97316;flex:0 0 auto}
.home-why-icon svg{width:29px;height:29px;stroke-width:1.9}
.home-why-item strong,.home-why-item small{display:block}
.home-why-item strong{font-size:12px;line-height:1.35;color:#1c1917}
.home-why-item small{font-size:10px;line-height:1.45;color:#78716c;margin-top:5px}
.home-legal-banner{display:flex;align-items:center;justify-content:space-between;gap:20px;margin-top:24px;padding:13px 16px;border:1px solid #fed7aa;border-radius:11px;background:linear-gradient(90deg,#fff7ed,#fff)}
.home-legal-banner strong,.home-legal-banner span{display:block}.home-legal-banner strong{font-size:13px;color:#1c1917}.home-legal-banner div>span{font-size:12px;color:#57534e;margin-top:3px}
.home-legal-banner>a{display:inline-flex;align-items:center;gap:9px;padding:9px 14px;border:1px solid #d6d3d1;border-radius:9px;background:#fff;font-size:12px;font-weight:600;white-space:nowrap}
.home-faq-section{padding-top:0;padding-bottom:52px}
.home-faq-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:8px 14px}
.home-faq-item{border:1px solid #e7e5e4;border-radius:10px;background:#fff;overflow:hidden}
.home-faq-item summary{list-style:none;display:flex;align-items:center;justify-content:space-between;gap:15px;padding:13px 16px;font-size:13px;font-weight:500;cursor:pointer}
.home-faq-item summary::-webkit-details-marker{display:none}
.home-faq-item summary svg{flex:0 0 auto;transition:transform .2s}.home-faq-item[open] summary svg{transform:rotate(180deg)}
.home-faq-item p{padding:0 16px 15px;color:#57534e;font-size:12px;line-height:1.65}
@media(max-width:1100px){
  .home-products-grid,.home-category-grid{grid-template-columns:repeat(3,minmax(0,1fr))}
  .home-why-grid{grid-template-columns:repeat(3,minmax(0,1fr))}
  .home-hero-copy{width:58%}
  .home-hero-benefits{grid-template-columns:repeat(3,minmax(0,1fr))}
  .home-hero-card{min-height:630px}
}
@media(max-width:767px){
  .home-hero{padding-top:8px}
  .home-hero-card{min-height:780px;border-radius:14px}
  .home-hero-image{object-position:66% center}
  .home-hero-overlay{background:linear-gradient(180deg,rgba(255,255,255,.98) 0%,rgba(255,255,255,.91) 42%,rgba(255,255,255,.52) 65%,rgba(255,255,255,.78) 100%)}
  .home-hero-copy{width:100%;padding:34px 22px 350px}
  .home-hero h1{font-size:42px}
  .home-hero-copy>p{font-size:14px}
  .home-hero-actions{align-items:stretch;flex-direction:column}.home-hero-actions a{justify-content:center;width:100%}
  .home-hero-benefits{left:14px;right:14px;bottom:14px;grid-template-columns:repeat(2,minmax(0,1fr));gap:7px}
  .home-mini-benefit{min-height:62px;padding:9px}.home-mini-benefit:last-child{grid-column:1/-1}
  .home-products-grid,.home-category-grid{grid-template-columns:repeat(2,minmax(0,1fr));gap:10px}
  .home-products-grid .card-img-wrap{height:155px!important}
  .home-category-image{height:145px}.home-category-info{padding:12px;min-height:82px}
  .home-section{padding:28px 0}.home-section-heading h2,.home-why-card>h2{font-size:20px}
  .home-why-grid{grid-template-columns:repeat(2,minmax(0,1fr));gap:18px 12px}
  .home-legal-banner{align-items:stretch;flex-direction:column}.home-legal-banner>a{justify-content:center}
  .home-faq-grid{grid-template-columns:1fr}
}
@media(max-width:420px){
  .home-hero h1{font-size:36px}
  .home-products-grid,.home-category-grid{grid-template-columns:1fr}
  .home-products-grid .card-img-wrap{height:230px!important}
}
</style>
@endsection
