@extends('layouts.app')
@section('title',$product->seoTitle())
@section('description',$product->seoDescription())
@section('canonical')<link rel="canonical" href="{{ $product->url }}">@endsection

@section('schema')
@php
$schemaProd=[
    '@context'=>'https://schema.org','@type'=>'Product',
    'name'=>$product->name,
    'description'=>$product->short_description?:Str::limit(strip_tags($product->description??''),200),
    'sku'=>$product->sku,
    'offers'=>[
        '@type'=>'Offer','priceCurrency'=>'KZT',
        'price'=>(float)$product->price,
        'availability'=>$product->in_stock?'https://schema.org/InStock':'https://schema.org/OutOfStock',
        'url'=>$product->url,
        'seller'=>['@type'=>'Organization','name'=>config('app.name')],
    ],
];
if($product->brand)$schemaProd['brand']=['@type'=>'Brand','name'=>$product->brand->name];
if($product->main_image)$schemaProd['image']=[asset('storage/'.$product->main_image)];
$bcItems=[['@type'=>'ListItem','position'=>1,'name'=>'Главная','item'=>url('/')]];
foreach($breadcrumbs as $i=>$bc){if(!empty($bc['url'])){$bcItems[]=['@type'=>'ListItem','position'=>$i+2,'name'=>$bc['name'],'item'=>$bc['url']];}}
$schemaBC=['@context'=>'https://schema.org','@type'=>'BreadcrumbList','itemListElement'=>$bcItems];
@endphp
<script type="application/ld+json">{!! json_encode($schemaProd,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES) !!}</script>
<script type="application/ld+json">{!! json_encode($schemaBC,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES) !!}</script>
@endsection

@section('breadcrumbs')
<a href="{{ route('home') }}">Главная</a>
<span class="bc-sep">/</span>
<a href="{{ route('catalog') }}">Каталог</a>
@if($product->category)
<span class="bc-sep">/</span>
<a href="{{ $product->category->url }}">{{ $product->category->name }}</a>
@endif
<span class="bc-sep">/</span><span>{{ Str::limit($product->name,40) }}</span>
@endsection

@section('content')
@php
$productHasDiscount = !empty($product->old_price) && (float)$product->old_price > (float)$product->price;
try { if (method_exists($product,'hasDiscount')) { $productHasDiscount = (bool)$product->hasDiscount(); } } catch(\Throwable $e) {}
@endphp
<style>
.prod-img-box{
    background:#f8f8f8;border-radius:16px;overflow:hidden;
    border:1px solid #eee;position:relative;margin-bottom:12px;
    max-height:340px;aspect-ratio:1;cursor:zoom-in;
}
/* Кнопка-обёртка галереи — поверх всего, без pointer-events конфликтов */
.prod-img-btn{
    display:block;width:100%;height:100%;
    background:none;border:none;padding:0;cursor:zoom-in;
    position:absolute;inset:0;z-index:2;
}
@media(min-width:768px){.prod-img-box{max-height:none}}

/* HTML-описание товара */
.product-content{max-width:900px;color:#444;line-height:1.8;font-size:15px}
.product-content p{margin:0 0 16px}
.product-content h2{font-size:22px;font-weight:700;color:#111;margin:28px 0 12px}
.product-content h3{font-size:18px;font-weight:700;color:#111;margin:22px 0 10px}
.product-content ul,.product-content ol{margin:14px 0 18px;padding-left:24px}
.product-content li{margin:0 0 8px}
.product-content strong{font-weight:700;color:#111}
.product-content a{color:#ff8a00;text-decoration:underline}

/* Характеристики — красивый список вместо простой таблицы */
.attrs-list{display:flex;flex-direction:column;gap:0}
.attrs-row{
    display:grid;
    grid-template-columns:1fr 1fr;
    gap:12px;
    padding:11px 16px;
    border-bottom:1px solid #f0f0f0;
    align-items:start;
    transition:background .15s;
}
.attrs-row:nth-child(even){background:#fafafa}
.attrs-row:last-child{border-bottom:none}
.attrs-row:hover{background:#fff8f0}
.attrs-key{font-size:13px;color:#666;line-height:1.4}
.attrs-val{font-size:13px;color:#111;font-weight:500;line-height:1.4;text-align:right}
@media(max-width:480px){
    .attrs-row{grid-template-columns:1fr;gap:3px;padding:10px 14px}
    .attrs-val{text-align:left;color:#333}
}

/* ── Product gallery clickable fix ── */
.prod-main-photo-btn{position:absolute;inset:0;z-index:2;width:100%;height:100%;border:0;background:transparent;padding:0;cursor:zoom-in;display:block}
.prod-main-photo-btn picture,.prod-main-photo-btn img{display:block;width:100%;height:100%}
.prod-main-photo-btn img{pointer-events:none}
.prod-photo-count{position:absolute;top:10px;right:10px;background:rgba(0,0,0,.56);color:#fff;font-size:12px;font-weight:700;padding:4px 9px;border-radius:99px;pointer-events:none;z-index:5;line-height:1.35;width:auto;max-width:max-content;white-space:nowrap}
.prod-gallery-arrow{position:absolute;top:50%;z-index:4;transform:translateY(-50%);width:42px;height:42px;border:0;border-radius:50%;background:rgba(255,255,255,.9);color:#111;box-shadow:0 8px 20px rgba(0,0,0,.12);display:grid;place-items:center;cursor:pointer;transition:background .2s,transform .2s}
.prod-gallery-arrow:hover{background:#fff;transform:translateY(-50%) scale(1.04)}
.prod-gallery-arrow--prev{left:12px}
.prod-gallery-arrow--next{right:12px}
.prod-gallery-arrow svg{width:18px;height:18px}
.prod-thumbs{display:flex;gap:8px;flex-wrap:wrap;margin-top:10px}
.prod-thumb{width:64px;height:64px;border-radius:10px;overflow:hidden;background:#f8f8f8;border:2px solid transparent;cursor:pointer;padding:4px;transition:border-color .2s,box-shadow .2s;flex-shrink:0}
.prod-thumb.is-active{border-color:#ff8a00;box-shadow:0 0 0 2px #ff8a0033}
.prod-thumb img{width:100%;height:100%;object-fit:contain;pointer-events:none}
.prod-lightbox[hidden]{display:none!important}
.prod-lightbox{position:fixed;inset:0;z-index:9999;background:rgba(0,0,0,.88);display:flex;align-items:center;justify-content:center;padding:16px}
.prod-lightbox img{max-width:min(90vw,1200px);max-height:90vh;object-fit:contain;border-radius:8px;box-shadow:0 24px 64px rgba(0,0,0,.5)}
.prod-lightbox-close{position:absolute;top:16px;right:16px;width:42px;height:42px;background:rgba(255,255,255,.15);border:0;border-radius:50%;color:#fff;font-size:30px;line-height:1;cursor:pointer;z-index:10000}
.prod-lightbox-close:hover{background:rgba(255,255,255,.3)}
@media(max-width:640px){
    .prod-gallery-arrow{width:38px;height:38px}
    .prod-gallery-arrow--prev{left:8px}
    .prod-gallery-arrow--next{right:8px}
}

.product-actions{display:flex;align-items:center;gap:12px;flex-wrap:wrap;margin-top:14px;margin-bottom:24px}
.product-actions>a{min-height:50px}
.kaspi-button-wrap{display:flex;align-items:center;min-height:50px}
.kaspi-button-wrap .ks-widget{display:inline-flex}
@media(max-width:640px){
    .product-actions{flex-direction:column;align-items:stretch}
    .product-actions>*{width:100%}
    .product-actions>a{justify-content:center}
}
</style>
@php $wa=\App\Services\CacheService::setting('whatsapp','');
$waMsg=urlencode('Хочу заказать: '.$product->name.' — '.$product->url); @endphp
@php
$kaspiMerchantCode = trim((string) (config('kaspi.merchant_code') ?: config('services.kaspi.merchant_code')));
$kaspiCityCode = trim((string) (config('kaspi.city_code') ?: config('services.kaspi.city_code')));
$showKaspiButton = trim((string) ($product->sku ?? '')) !== '' && $kaspiMerchantCode !== '' && $kaspiCityCode !== '';
@endphp

<div class="container" style="padding-top:24px;padding-bottom:48px">
  <div class="product-page-grid">

    {{-- ── ГАЛЕРЕЯ: без Alpine, на обычном JavaScript ── --}}
    @php
    $allImages = [];
    $seenImagePaths = [];
    $appendImage = function ($path, $alt = null, $webp = null) use (&$allImages, &$seenImagePaths, $product) {
        $normalizedPath = ltrim(trim((string) $path), '/');
        if ($normalizedPath === '' || isset($seenImagePaths[$normalizedPath])) {
            return;
        }

        $normalizedWebp = ltrim(trim((string) $webp), '/');
        $seenImagePaths[$normalizedPath] = true;
        $allImages[] = [
            'src' => asset('storage/'.$normalizedPath),
            'webp' => $normalizedWebp !== '' ? asset('storage/'.$normalizedWebp) : '',
            'alt' => $alt ?: $product->name,
        ];
    };

    $appendImage($product->main_image, $product->main_image_alt, $product->main_image_webp);
    if (method_exists($product, 'images')) {
        foreach ($product->images as $img) {
            $appendImage($img->path, $img->alt, $img->path_webp ?? null);
        }
    }
    $firstSrc = $allImages[0]['src'] ?? '';
    $firstWebp = $allImages[0]['webp'] ?? '';
    @endphp

    <div class="product-gallery" data-product-gallery>
      {{-- Главное фото --}}
      <div class="prod-img-box">
        @if($firstSrc)
        <button type="button" class="prod-main-photo-btn" data-open-lightbox aria-label="Открыть фото">
          <picture>
            <source data-main-source srcset="{{ $firstWebp }}" type="image/webp" @if(!$firstWebp) disabled @endif>
            <img data-main-image src="{{ $firstSrc }}"
                 alt="{{ $product->name }}"
                 style="width:100%;height:100%;object-fit:contain;padding:24px;display:block"
                 loading="eager" fetchpriority="high">
          </picture>
        </button>
        @else
        <div style="width:100%;height:100%;display:flex;align-items:center;justify-content:center">
          <svg width="80" height="80" fill="#ddd" viewBox="0 0 100 100"><path d="M50 15C35 15 25 25 25 40v20c0 5 3 9 7 10l-2 10h10l2-10h16l2 10h10l-2-10c4-1 7-5 7-10V40c0-15-10-25-25-25z" opacity=".3"/></svg>
        </div>
        @endif

        @if(count($allImages) > 1)
        <button type="button" class="prod-gallery-arrow prod-gallery-arrow--prev" data-gallery-prev aria-label="Предыдущее фото">
          <svg fill="none" stroke="currentColor" stroke-width="2.4" viewBox="0 0 24 24"><path d="M15 18l-6-6 6-6" stroke-linecap="round" stroke-linejoin="round"/></svg>
        </button>
        <button type="button" class="prod-gallery-arrow prod-gallery-arrow--next" data-gallery-next aria-label="Следующее фото">
          <svg fill="none" stroke="currentColor" stroke-width="2.4" viewBox="0 0 24 24"><path d="M9 6l6 6-6 6" stroke-linecap="round" stroke-linejoin="round"/></svg>
        </button>
        <div class="prod-photo-count" data-photo-counter>1 / {{ count($allImages) }}</div>
        @endif

        <div style="position:absolute;top:10px;left:10px;display:flex;flex-direction:column;gap:6px;z-index:3;pointer-events:none">
          @if($product->is_new)<span class="badge badge-new">Новинка</span>@endif
          @if($product->is_hit)<span class="badge badge-hit">Хит продаж</span>@endif
        </div>
      </div>

      {{-- Миниатюры --}}
      @if(count($allImages) > 1)
      <div class="prod-thumbs">
        @foreach($allImages as $i => $img)
        <button type="button"
                class="prod-thumb {{ $i === 0 ? 'is-active' : '' }}"
                data-thumb
                data-index="{{ $i }}"
                data-src="{{ $img['src'] }}"
                data-webp="{{ $img['webp'] }}"
                data-alt="{{ $img['alt'] }}"
                aria-label="Показать фото {{ $i + 1 }}">
          <picture>
            @if($img['webp'])
            <source srcset="{{ $img['webp'] }}" type="image/webp">
            @endif
            <img src="{{ $img['src'] }}" alt="{{ $img['alt'] }}" loading="lazy">
          </picture>
        </button>
        @endforeach
      </div>
      @endif

      {{-- Lightbox --}}
      <div class="prod-lightbox" data-lightbox hidden>
        <button type="button" class="prod-lightbox-close" data-close-lightbox aria-label="Закрыть">×</button>
        <img data-lightbox-image src="{{ $firstSrc }}" alt="{{ $product->name }}">
      </div>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('[data-product-gallery]').forEach(function (gallery) {
            var mainImage = gallery.querySelector('[data-main-image]');
            var mainSource = gallery.querySelector('[data-main-source]');
            var mainButton = gallery.querySelector('[data-open-lightbox]');
            var lightbox = gallery.querySelector('[data-lightbox]');
            var lightboxImage = gallery.querySelector('[data-lightbox-image]');
            var closeButton = gallery.querySelector('[data-close-lightbox]');
            var counter = gallery.querySelector('[data-photo-counter]');
            var prevButton = gallery.querySelector('[data-gallery-prev]');
            var nextButton = gallery.querySelector('[data-gallery-next]');
            var thumbs = Array.prototype.slice.call(gallery.querySelectorAll('[data-thumb]'));
            var images = thumbs.map(function (btn) {
                return {
                    src: btn.getAttribute('data-src'),
                    webp: btn.getAttribute('data-webp'),
                    alt: btn.getAttribute('data-alt') || ''
                };
            });
            var activeIndex = 0;
            var activeSrc = mainImage ? mainImage.getAttribute('src') : '';

            function setActive(index) {
                if (!images.length) return;
                if (index < 0) index = images.length - 1;
                if (index >= images.length) index = 0;

                var image = images[index];
                var src = image.src;
                var alt = image.alt;
                if (!src || !mainImage) return;

                activeIndex = index;
                activeSrc = src;
                mainImage.setAttribute('src', src);
                if (alt) mainImage.setAttribute('alt', alt);

                if (mainSource) {
                    if (image.webp) {
                        mainSource.setAttribute('srcset', image.webp);
                        mainSource.removeAttribute('disabled');
                    } else {
                        mainSource.removeAttribute('srcset');
                        mainSource.setAttribute('disabled', 'disabled');
                    }
                }

                thumbs.forEach(function (btn, btnIndex) {
                    btn.classList.toggle('is-active', btnIndex === index);
                });

                if (counter) {
                    counter.textContent = (index + 1) + ' / ' + images.length;
                }

                if (lightbox && !lightbox.hasAttribute('hidden') && lightboxImage) {
                    lightboxImage.setAttribute('src', src);
                    if (alt) lightboxImage.setAttribute('alt', alt);
                }
            }

            function shiftImage(step) {
                if (images.length < 2) return;
                setActive(activeIndex + step);
            }

            function openLightbox() {
                if (!activeSrc || !lightbox || !lightboxImage) return;
                lightboxImage.setAttribute('src', activeSrc);
                lightbox.removeAttribute('hidden');
                document.body.style.overflow = 'hidden';
            }

            function closeLightbox() {
                if (!lightbox) return;
                lightbox.setAttribute('hidden', 'hidden');
                document.body.style.overflow = '';
            }

            if (mainButton) {
                mainButton.addEventListener('click', function (e) {
                    e.preventDefault();
                    openLightbox();
                });
            }

            thumbs.forEach(function (btn, index) {
                btn.addEventListener('click', function (e) {
                    e.preventDefault();
                    setActive(index);
                });
            });

            if (prevButton) {
                prevButton.addEventListener('click', function (e) {
                    e.preventDefault();
                    e.stopPropagation();
                    shiftImage(-1);
                });
            }

            if (nextButton) {
                nextButton.addEventListener('click', function (e) {
                    e.preventDefault();
                    e.stopPropagation();
                    shiftImage(1);
                });
            }

            var touchStartX = null;
            if (mainButton) {
                mainButton.addEventListener('touchstart', function (e) {
                    touchStartX = e.changedTouches[0].clientX;
                }, { passive: true });
                mainButton.addEventListener('touchend', function (e) {
                    if (touchStartX === null) return;
                    var delta = e.changedTouches[0].clientX - touchStartX;
                    touchStartX = null;
                    if (Math.abs(delta) < 40) return;
                    e.preventDefault();
                    shiftImage(delta > 0 ? -1 : 1);
                });
            }

            if (closeButton) {
                closeButton.addEventListener('click', function (e) {
                    e.preventDefault();
                    closeLightbox();
                });
            }

            if (lightbox) {
                lightbox.addEventListener('click', function (e) {
                    if (e.target === lightbox) closeLightbox();
                });
            }

            document.addEventListener('keydown', function (e) {
                if (e.key === 'Escape') closeLightbox();
            });
        });
    });
    </script>

    {{-- ── ИНФОРМАЦИЯ ── --}}
    <div>
      @if($product->brand)
      <a href="{{ route('brand.show',$product->brand->slug) }}"
         style="font-size:13px;font-weight:600;color:#ff8a00;letter-spacing:.05em;text-transform:uppercase;margin-bottom:8px;display:inline-block">
          {{ $product->brand->name }}
      </a>
      @endif

      <h1 style="font-size:clamp(1.3rem,3vw,1.7rem);font-weight:700;color:#111;line-height:1.3;margin-bottom:16px">
          {{ $product->name }}
      </h1>

      @if($product->is_hit)
      <div style="margin-bottom:12px"><span class="badge badge-hit" style="font-size:12px;padding:3px 10px">Хит продаж</span></div>
      @endif

      <div style="display:flex;align-items:baseline;gap:12px;margin-bottom:12px">
        <span style="font-size:2rem;font-weight:800;color:#111">{{ number_format($product->price,0,'.',' ') }} ₸</span>
        @if($productHasDiscount)
        <span style="font-size:16px;color:#aaa;text-decoration:line-through">{{ number_format($product->old_price,0,'.',' ') }} ₸</span>
        @endif
      </div>

      @if($product->in_stock)
      <div style="display:flex;align-items:center;gap:6px;font-size:14px;color:#16a34a;margin-bottom:8px">
        <div style="width:8px;height:8px;background:#22c55e;border-radius:50%"></div> В наличии
      </div>
      @else
      <div style="font-size:14px;color:#aaa;margin-bottom:8px">Нет в наличии</div>
      @endif

      @if($product->sku)
      <div style="font-size:13px;color:#999;margin-bottom:20px">Артикул: {{ $product->sku }}</div>
      @endif

      <div class="product-actions">
        @if($wa)
        <a href="https://wa.me/{{ $wa }}?text={{ $waMsg }}" target="_blank" rel="noopener"
           class="btn-wa" style="justify-content:center;font-size:16px;padding:15px">
          <svg class="wa-svg" style="width:20px;height:20px" viewBox="0 0 24 24"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413z"/></svg>
          Купить в WhatsApp
        </a>
        <a href="https://wa.me/{{ $wa }}" target="_blank" rel="noopener"
           style="display:flex;align-items:center;justify-content:center;gap:8px;padding:13px;
                  border:1.5px solid #eee;border-radius:10px;font-size:14px;font-weight:500;
                  color:#333;transition:border-color .2s"
           onmouseover="this.style.borderColor='#ff8a00'" onmouseout="this.style.borderColor='#eee'">
          Быстрая консультация
          <svg width="15" height="15" fill="#22c55e" viewBox="0 0 24 24"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413z"/></svg>
        </a>
        @endif
        @if($showKaspiButton)
        <div class="kaspi-button-wrap">
          <x-kaspi.credit-button :product="$product" />
        </div>
        @endif
      </div>

      <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px">
        @foreach([['🚚','Доставка по Алматы','1–2 дня'],['🏅','Гарантия','от 12 месяцев'],['↩️','Возврат','14 дней'],['💳','Оплата','Kaspi, карта, нал']] as [$icon,$t,$d])
        <div style="display:flex;align-items:center;gap:8px;padding:10px;background:#f8f8f8;border-radius:10px">
          <span style="font-size:18px">{{ $icon }}</span>
          <div>
            <div style="font-size:11px;font-weight:600;color:#111">{{ $t }}</div>
            <div style="font-size:11px;color:#999">{{ $d }}</div>
          </div>
        </div>
        @endforeach
      </div>
    </div>
  </div>

  {{-- ── ОПИСАНИЕ + ХАРАКТЕРИСТИКИ (без вкладок, всегда видны) ── --}}
  @php $attrs = $product->attributes_array ?? []; @endphp

  @if($product->description)
  <div style="margin-bottom:36px">
    <h2 style="font-size:20px;font-weight:700;color:#111;margin-bottom:16px;
               padding-bottom:10px;border-bottom:2px solid #ff8a00;display:inline-block">
        Описание
    </h2>
    <div class="prose product-content">
      {!! $product->description !!}
    </div>
  </div>
  @endif

  @if(!empty($attrs))
  <div style="margin-bottom:36px">
    <h2 style="font-size:20px;font-weight:700;color:#111;margin-bottom:16px;
               padding-bottom:10px;border-bottom:2px solid #ff8a00;display:inline-block">
        Характеристики
    </h2>
    <div style="border:1px solid #eee;border-radius:14px;overflow:hidden">
      <div class="attrs-list">
        @foreach($attrs as $key => $val)
        <div class="attrs-row">
          <span class="attrs-key">{{ $key }}</span>
          <span class="attrs-val">{{ $val }}</span>
        </div>
        @endforeach
      </div>
    </div>
  </div>
  @endif

  {{-- ── FAQ ── --}}
  @php $productFaq = $product->faq_array ?? []; @endphp
  @if(!empty($productFaq))
  <div style="margin-bottom:36px" x-data="{open:null}">
    <h2 style="font-size:20px;font-weight:700;color:#111;margin-bottom:16px">Вопросы и ответы</h2>
    @foreach($productFaq as $i => $faq)
    <div class="faq-item">
      <button class="faq-q" @click="open===`p{{ $i }}`?open=null:open=`p{{ $i }}`">
        {{ $faq['question'] ?? '' }}
        <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"
             :style="open===`p{{ $i }}`?'transform:rotate(180deg)':''"
             style="flex-shrink:0;transition:transform .2s">
          <path d="M6 9l6 6 6-6"/>
        </svg>
      </button>
      <div x-show="open===`p{{ $i }}`" class="faq-a">{{ $faq['answer'] ?? '' }}</div>
    </div>
    @endforeach
  </div>
  @endif

  {{-- ── ПОХОЖИЕ ТОВАРЫ ── --}}
  @if(($similar ?? collect())->count())
  <div>
    <h2 style="font-size:20px;font-weight:700;color:#111;margin-bottom:20px">Похожие товары</h2>
    <div class="pgrid">
      @foreach($similar->take(4) as $p)
        @include('components.product.card', ['product' => $p])
      @endforeach
    </div>
  </div>
  @endif
</div>

@php $wa = \App\Services\CacheService::setting('whatsapp', ''); @endphp
<div style="background:#111;padding:40px 16px;text-align:center">
  <p style="color:#fff;font-weight:700;font-size:18px;margin-bottom:8px">Нужна помощь с выбором?</p>
  <p style="color:#aaa;font-size:14px;margin-bottom:20px">Напишите нам в WhatsApp — подберём кресло под ваши задачи</p>
  @if($wa)
  <a href="https://wa.me/{{ $wa }}" target="_blank" class="btn-wa">
    <svg class="wa-svg" viewBox="0 0 24 24"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413z"/></svg>
    Написать в WhatsApp
  </a>
  @endif
</div>

@if($showKaspiButton)
@push('scripts')
@once
<script>
(function(d, s, id) {
    var js, kjs;
    if (d.getElementById(id)) return;
    js = d.createElement(s); js.id = id;
    js.src = 'https://kaspi.kz/kaspibutton/widget/ks-wi_ext.js';
    kjs = document.getElementsByTagName(s)[0];
    kjs.parentNode.insertBefore(js, kjs);
}(document, 'script', 'KS-Widget'));
</script>
@endonce
@endpush
@endif
@endsection
