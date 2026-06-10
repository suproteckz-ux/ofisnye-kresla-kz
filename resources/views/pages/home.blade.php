@extends('layouts.app')
@section('title','Офисные кресла в Алматы — купить с доставкой | '.config('app.name'))
@section('description','Интернет-магазин офисных кресел в Алматы. Более '.($totalProducts??'79').' моделей: кресла для руководителей, эргономичные, компьютерные, игровые. Доставка по Казахстану.')
@section('canonical')<link rel="canonical" href="{{ url('/') }}">@endsection

@section('schema')
@php
$sw=['@context'=>'https://schema.org','@type'=>'WebSite','name'=>config('app.name'),'url'=>config('app.url'),'potentialAction'=>['@type'=>'SearchAction','target'=>config('app.url').'/search?q={q}','query-input'=>'required name=q']];
@endphp
<script type="application/ld+json">{!! json_encode($sw,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES) !!}</script>
@endsection

@section('content')
@php $wa = \App\Services\CacheService::setting('whatsapp',''); @endphp

{{-- ═══ HERO ═══ --}}
<section class="hero-section">
  <div class="container">
  {{-- Desktop: text+image grid. Mobile: single column --}}
  <div class="hero-inner">

    {{-- Left: text content --}}
    <div class="hero-text">
      <div style="font-size:13px;font-weight:600;color:#ff8a00;margin-bottom:14px;display:flex;align-items:center;gap:6px">
        <div style="width:8px;height:8px;background:#ff8a00;border-radius:50%;flex-shrink:0"></div>
        Более {{ $totalProducts ?? 79 }} моделей в наличии
      </div>
      <h1 style="font-size:clamp(2rem,4vw,3rem);font-weight:800;color:#111;line-height:1.15;margin-bottom:16px">
        Офисные кресла<br>в Алматы
      </h1>
      <p style="font-size:15px;color:#555;line-height:1.7;margin-bottom:28px;max-width:400px">
        Кресла для руководителей, эргономичные, компьютерные и игровые. Доставка по Алматы и Казахстану.
      </p>
      <div style="display:flex;flex-wrap:wrap;gap:12px;margin-bottom:28px">
        <a href="{{ route('catalog') }}" class="btn-orange">
          Перейти в каталог
          <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
        </a>
        @if($wa)
        <a href="https://wa.me/{{ $wa }}" target="_blank" rel="noopener" class="btn-outline">
          Консультация в WhatsApp
        </a>
        @endif
      </div>
      {{-- Advantages 2x2 --}}
      <div class="hero-adv-grid">
        @foreach([
          ['🚚','Доставка по Алматы','1–2 дня'],
          ['🛡','Гарантия','от 12 месяцев'],
          ['↩️','Возврат','14 дней'],
          ['💳','Оплата','Наличные, Kaspi, карта'],
        ] as [$icon,$t,$d])
        <div class="adv-item" style="padding:10px">
          <div style="font-size:18px;flex-shrink:0">{{ $icon }}</div>
          <div>
            <div class="adv-title" style="font-size:12px">{{ $t }}</div>
            <div class="adv-desc">{{ $d }}</div>
          </div>
        </div>
        @endforeach
      </div>
    </div>

    {{-- Right: hero image (hidden on mobile) --}}
    <div class="hero-img-wrap">
      @if($heroProduct??null)
      <img src="{{ asset('storage/'.$heroProduct->main_image) }}" alt="{{ $heroProduct->name }}"
           style="width:100%;height:100%;object-fit:contain;object-position:center bottom;padding:32px" loading="eager">
      @else
      <div style="display:flex;align-items:center;justify-content:center;height:100%;min-height:380px">
        <svg width="200" height="200" fill="#ccc" viewBox="0 0 100 100"><path d="M50 15C35 15 25 25 25 40v20c0 5 3 9 7 10l-2 10h10l2-10h16l2 10h10l-2-10c4-1 7-5 7-10V40c0-15-10-25-25-25z"/></svg>
      </div>
      @endif
    </div>
  </div>
  </div>{{-- /container --}}
</section>

<style>
/* HERO */
.hero-section{background:#fff;overflow:hidden}
.hero-inner{display:flex;flex-direction:column;min-height:auto}
.hero-text{padding:32px 16px 28px;max-width:100%}
.hero-img-wrap{display:none}
.hero-adv-grid{display:grid;grid-template-columns:1fr 1fr;gap:10px}

@media(min-width:768px){
  .hero-inner{flex-direction:row;align-items:stretch;min-height:460px;margin:0 -32px 0 0}
  .hero-text{flex:0 0 420px;padding:48px 32px 48px 0}
  .hero-img-wrap{
    flex:1;display:flex;align-items:center;justify-content:center;
    background:#f0f4f0;overflow:hidden;
    border-radius:0 0 0 24px;
    min-height:460px;margin-right:-32px;
  }
}
</style>

{{-- ═══ HITS ═══ --}}
@php
$hitsToShow = ($hits??collect())->count() > 0 ? $hits : collect();
// Fallback: any active products
if($hitsToShow->count() === 0) {
  $hitsToShow = \Illuminate\Support\Facades\Cache::remember('home.hits.fallback', 1800, fn() =>
    \App\Models\Product::active()->with(['brand:id,name,slug'])->orderByDesc('views')->limit(8)->get()
  );
}
@endphp
@if($hitsToShow->count() > 0)
<section class="section">
  <div class="container">
    <div class="section-header">
      <h2 class="section-title">Хиты продаж</h2>
      <a href="{{ route('catalog') }}" class="see-all">Смотреть все →</a>
    </div>
    <div class="pgrid-5">
      @foreach($hitsToShow->take(5) as $product)
        @include('components.product.card',['product'=>$product])
      @endforeach
    </div>
  </div>
</section>
@endif

{{-- ═══ ADVANTAGES STRIP ═══ --}}
<section style="padding:32px 0;background:#f8f8f8">
  <div class="container">
    <div class="adv-strip">
      @foreach([
        ['🚚','Бесплатная доставка','по Алматы от 20 000 ₸'],
        ['🏅','Официальная гарантия','от 12 месяцев'],
        ['💬','Сервис и поддержка','Поможем с выбором'],
        ['💳','Безопасная оплата','Наличные, Kaspi, карта'],
      ] as [$icon,$t,$d])
      <div class="adv-item">
        <div style="font-size:24px;flex-shrink:0">{{ $icon }}</div>
        <div>
          <div class="adv-title">{{ $t }}</div>
          <div class="adv-desc">{{ $d }}</div>
        </div>
      </div>
      @endforeach
    </div>
  </div>
</section>

{{-- ═══ WHY US ═══ --}}
<section class="section">
  <div class="container">
    <div class="why-grid">
      <div>
        <div style="font-size:12px;font-weight:600;color:#ff8a00;letter-spacing:.08em;text-transform:uppercase;margin-bottom:10px">ПОЧЕМУ ВЫБИРАЮТ НАС</div>
        <h2 style="font-size:clamp(1.4rem,3vw,2rem);font-weight:800;color:#111;line-height:1.3;margin-bottom:16px">
          Комфорт на каждый день<br>для работы и отдыха
        </h2>
        <p style="color:#555;font-size:14px;line-height:1.7;margin-bottom:24px">
          Мы подбираем кресла, которые помогают сохранять правильную осанку, снижают усталость и повышают продуктивность.
        </p>
        <div style="display:flex;flex-direction:column;gap:12px;margin-bottom:28px">
          @foreach([
            'Только проверенные модели',
            'Оптимальное соотношение цены и качества',
            'Быстрая доставка и удобный сервис',
          ] as $item)
          <div style="display:flex;align-items:center;gap:10px;font-size:14px;color:#333">
            <div style="width:20px;height:20px;background:#ff8a00;border-radius:50%;display:flex;align-items:center;justify-content:center;flex-shrink:0">
              <svg width="10" height="10" fill="none" stroke="#fff" stroke-width="2.5" viewBox="0 0 24 24"><path d="M5 13l4 4L19 7"/></svg>
            </div>
            {{ $item }}
          </div>
          @endforeach
        </div>
        <a href="{{ route('catalog') }}" class="btn-orange" style="font-size:14px;padding:11px 20px">О компании</a>
      </div>
      <div style="background:#f0f0f0;border-radius:20px;overflow:hidden;min-height:280px;display:flex;align-items:center;justify-content:center">
        <div style="padding:32px;text-align:center">
          <div style="font-size:80px;margin-bottom:12px">🏢</div>
          <div style="font-size:14px;color:#999">Наш шоурум в Алматы</div>
        </div>
      </div>
    </div>
  </div>
</section>

{{-- ═══ NEW PRODUCTS ═══ --}}
@php $newShow = ($newProducts??collect()); @endphp
@if($newShow->count() > 0)
<section class="section" style="background:#f8f8f8">
  <div class="container">
    <div class="section-header">
      <h2 class="section-title">Новинки</h2>
      <a href="{{ route('catalog') }}" class="see-all">Смотреть все →</a>
    </div>
    <div class="pgrid">
      @foreach($newShow->take(4) as $product)
        @include('components.product.card',['product'=>$product])
      @endforeach
    </div>
  </div>
</section>
@endif

{{-- ═══ REVIEWS ═══ --}}
<section class="section">
  <div class="container">
    <div class="section-header">
      <h2 class="section-title">Отзывы наших клиентов</h2>
      <a href="{{ route('catalog') }}" class="see-all">Смотреть все →</a>
    </div>
    <div class="reviews-grid">
      @foreach([
        ['Айгуль М.','12 мая 2025','Отличное кресло, очень удобно! Доставка быстрая, собрали за 10 минут. Спасибо!'],
        ['Руслан К.','8 мая 2025','Покупал кресло 1361А, качество супер. Спина больше не болит после работы. Рекомендую!'],
        ['Динара С.','28 апреля 2025','Вежливые менеджеры, помогли с выбором. Кресло красивое и комфортное.'],
      ] as [$name,$date,$text])
      <div class="card" style="padding:20px">
        <div style="display:flex;align-items:center;gap:10px;margin-bottom:12px">
          <div style="width:40px;height:40px;background:#ff8a00;border-radius:50%;display:flex;align-items:center;justify-content:center;font-weight:700;color:#fff;font-size:15px;flex-shrink:0">{{ mb_substr($name,0,1) }}</div>
          <div>
            <div style="font-weight:600;font-size:14px;color:#111">{{ $name }}</div>
            <div style="font-size:12px;color:#999">{{ $date }}</div>
          </div>
        </div>
        <div class="stars">★★★★★</div>
        <p style="font-size:14px;color:#444;line-height:1.6;margin-top:10px">{{ $text }}</p>
      </div>
      @endforeach
    </div>
  </div>
</section>

{{-- ═══ FAQ ═══ --}}
<section class="section" style="background:#f8f8f8">
  <div class="container" style="max-width:800px">
    <div class="section-header">
      <h2 class="section-title">Частые вопросы</h2>
    </div>
    <div x-data="{open:null}">
      @foreach([
        ['Как заказать офисное кресло в Алматы?','Позвоните нам или напишите в WhatsApp — поможем с выбором и оформим доставку в удобное время.'],
        ['Есть ли самовывоз из магазина?','Да, вы можете забрать кресло из нашего шоурума в Алматы. Адрес уточняйте при заказе.'],
        ['Можно ли вернуть кресло, если не подошло?','Да, в течение 14 дней при сохранении товарного вида и упаковки.'],
        ['Доставляете ли в другие города?','Да, доставляем по всему Казахстану: Астана, Шымкент, Актобе и другие. Сроки 3–7 дней.'],
        ['Какой срок гарантии на кресла?','12 месяцев на механизмы и газлифт, 6 месяцев на обивку.'],
      ] as $i => [$q,$a])
      <div class="faq-item">
        <button class="faq-q" @click="open=open==={{ $i }}?null:{{ $i }}">
          {{ $q }}
          <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"
               :style="open==={{ $i }}?'transform:rotate(180deg)':''" style="flex-shrink:0;transition:transform .2s">
            <path d="M6 9l6 6 6-6"/>
          </svg>
        </button>
        <div x-show="open==={{ $i }}" class="faq-a">{{ $a }}</div>
      </div>
      @endforeach
    </div>
  </div>
</section>


@endsection
