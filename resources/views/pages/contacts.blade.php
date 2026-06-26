@extends('layouts.app')

@section('title', 'Контакты — Офисные кресла Алматы')
@section('description', 'Адрес шоурума офисных кресел в Алматы: ул. Муратбаева 138. Телефон, WhatsApp, режим работы и карта проезда.')

@section('canonical')
<link rel="canonical" href="{{ route('contacts') }}">
@endsection

@section('breadcrumbs')
<x-ui.breadcrumbs :items="[['name' => 'Контакты', 'url' => route('contacts')]]"/>
@endsection

@section('content')
@php
    $cleanPhone = preg_replace('/\D/', '', $phone);
    $cleanWhatsapp = preg_replace('/\D/', '', $whatsapp);
    $waText = urlencode('Здравствуйте! Хочу приехать в шоурум офисных кресел на Муратбаева 138.');
@endphp

<section class="contacts-page">
    <div class="container">
        <div class="contacts-hero">
            <div class="contacts-hero__copy">
                <span class="contacts-eyebrow">Шоурум в Алматы</span>
                <h1>Контакты шоурума офисных кресел в Алматы</h1>
                <p>Приходите в наш шоурум, чтобы лично протестировать офисные кресла, сравнить модели и получить консультацию по выбору.</p>
                <div class="contacts-actions">
                    <a href="https://wa.me/{{ $cleanWhatsapp }}?text={{ $waText }}" target="_blank" rel="noopener" class="contacts-btn contacts-btn--wa">
                        WhatsApp
                    </a>
                    <a href="{{ $routeUrl }}" target="_blank" rel="noopener" class="contacts-btn contacts-btn--route">
                        Построить маршрут
                    </a>
                </div>
            </div>

            <div class="contacts-info">
                <article>
                    <span>Телефон</span>
                    <a href="tel:{{ $cleanPhone }}">{{ $phone }}</a>
                </article>
                <article>
                    <span>Адрес</span>
                    <strong>{{ $address }}</strong>
                </article>
                <article>
                    <span>Режим работы</span>
                    <strong>{!! nl2br(e($workingHours)) !!}</strong>
                </article>
            </div>
        </div>

        <div class="contacts-media-grid">
            <section class="showroom-gallery" data-showroom-gallery>
                <div class="showroom-gallery__main">
                    @foreach($showroomPhotos as $index => $photo)
                    <picture class="showroom-gallery__slide {{ $index === 0 ? 'is-active' : '' }}" data-showroom-slide>
                        <source srcset="{{ $photo['webp'] }}" type="image/webp">
                        <img src="{{ $photo['jpg'] }}"
                             alt="{{ $photo['alt'] }}"
                             width="1600"
                             height="1200"
                             loading="{{ $index === 0 ? 'eager' : 'lazy' }}"
                             fetchpriority="{{ $index === 0 ? 'high' : 'auto' }}"
                             decoding="async">
                    </picture>
                    @endforeach

                    <button type="button" class="showroom-gallery__arrow showroom-gallery__arrow--prev" data-showroom-prev aria-label="Предыдущее фото">
                        <svg width="22" height="22" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M15 18l-6-6 6-6"/></svg>
                    </button>
                    <button type="button" class="showroom-gallery__arrow showroom-gallery__arrow--next" data-showroom-next aria-label="Следующее фото">
                        <svg width="22" height="22" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M9 18l6-6-6-6"/></svg>
                    </button>
                    <div class="showroom-gallery__count"><span data-showroom-current>1</span> / {{ count($showroomPhotos) }}</div>
                </div>

                <div class="showroom-gallery__thumbs" aria-label="Фотографии шоурума">
                    @foreach($showroomPhotos as $index => $photo)
                    <button type="button" class="showroom-gallery__thumb {{ $index === 0 ? 'is-active' : '' }}" data-showroom-thumb="{{ $index }}" aria-label="Показать фото {{ $index + 1 }}">
                        <picture>
                            <source srcset="{{ $photo['webp'] }}" type="image/webp">
                            <img src="{{ $photo['jpg'] }}" alt="{{ $photo['alt'] }}" width="180" height="120" loading="lazy" decoding="async">
                        </picture>
                    </button>
                    @endforeach
                </div>
            </section>

            <aside class="contacts-map">
                <div class="contacts-map__visual">
                    <div>
                        <span>2GIS</span>
                        <strong>г. Алматы, ул. Муратбаева 138</strong>
                    </div>
                </div>
                <div class="contacts-map__body">
                    <h2>Карта проезда</h2>
                    <p>Откройте маршрут в 2GIS, чтобы построить удобный путь до шоурума.</p>
                    <a href="{{ $routeUrl }}" target="_blank" rel="noopener" class="contacts-btn contacts-btn--route">Открыть в 2GIS</a>
                </div>
            </aside>
        </div>

        <section class="contacts-find">
            <h2>Как нас найти</h2>
            <p>Шоурум находится по адресу г. Алматы, ул. Муратбаева 138. В наличии офисные кресла для дома, офиса, руководителей и сотрудников. Можно приехать, посидеть на разных моделях и выбрать подходящий вариант.</p>
        </section>
    </div>
</section>

<style>
.contacts-page{padding:30px 0 64px;background:#fff}
.contacts-hero{display:grid;grid-template-columns:1fr;gap:18px;margin-bottom:28px}
.contacts-hero__copy{padding:26px;border:1px solid #eee;border-radius:18px;background:#fff;box-shadow:0 10px 30px rgba(17,17,17,.05)}
.contacts-eyebrow{display:inline-flex;margin-bottom:10px;color:#ff8a00;font-size:12px;font-weight:800;text-transform:uppercase;letter-spacing:.04em}
.contacts-hero h1{font-size:clamp(2rem,4vw,3.15rem);line-height:1.08;font-weight:900;color:#111;margin:0 0 14px}
.contacts-hero p{font-size:16px;line-height:1.7;color:#555;margin:0;max-width:720px}
.contacts-actions{display:flex;gap:10px;flex-wrap:wrap;margin-top:22px}
.contacts-btn{display:inline-flex;align-items:center;justify-content:center;min-height:44px;padding:12px 18px;border-radius:12px;font-size:14px;font-weight:800;text-decoration:none;border:1px solid transparent;transition:transform .18s,box-shadow .18s,background .18s}
.contacts-btn--wa{background:#22c55e;color:#fff}.contacts-btn--wa:hover{background:#16a34a;color:#fff}
.contacts-btn--route{background:#ff8a00;color:#fff}.contacts-btn--route:hover{background:#f97316;color:#fff}
.contacts-info{display:grid;grid-template-columns:1fr;gap:12px}
.contacts-info article{padding:18px;border:1px solid #eee;border-radius:16px;background:#fafafa}
.contacts-info span{display:block;font-size:12px;color:#888;margin-bottom:7px}
.contacts-info a,.contacts-info strong{font-size:17px;line-height:1.45;color:#111;font-weight:800;text-decoration:none}
.contacts-media-grid{display:grid;grid-template-columns:1fr;gap:18px;align-items:start;margin-bottom:24px}
.showroom-gallery,.contacts-map,.contacts-find{border:1px solid #eee;border-radius:18px;background:#fff;box-shadow:0 10px 30px rgba(17,17,17,.05);overflow:hidden}
.showroom-gallery__main{position:relative;aspect-ratio:16/10;background:#f8f8f8;overflow:hidden}
.showroom-gallery__slide{position:absolute;inset:0;opacity:0;transform:scale(1.015);transition:opacity .24s ease,transform .24s ease}
.showroom-gallery__slide.is-active{opacity:1;transform:scale(1)}
.showroom-gallery__slide img{width:100%;height:100%;object-fit:cover;display:block}
.showroom-gallery__arrow{position:absolute;top:50%;z-index:2;transform:translateY(-50%);width:44px;height:44px;border:0;border-radius:999px;background:rgba(255,255,255,.9);color:#111;display:grid;place-items:center;box-shadow:0 8px 20px rgba(0,0,0,.14);cursor:pointer}
.showroom-gallery__arrow--prev{left:14px}.showroom-gallery__arrow--next{right:14px}
.showroom-gallery__count{position:absolute;right:14px;top:14px;z-index:2;padding:5px 10px;border-radius:99px;background:rgba(0,0,0,.55);color:#fff;font-size:12px;font-weight:800}
.showroom-gallery__thumbs{display:flex;gap:9px;padding:12px;overflow-x:auto;overscroll-behavior-x:contain}
.showroom-gallery__thumb{width:92px;height:64px;flex:0 0 92px;border:2px solid transparent;border-radius:12px;padding:0;overflow:hidden;background:#f8f8f8;cursor:pointer}
.showroom-gallery__thumb.is-active{border-color:#ff8a00;box-shadow:0 0 0 2px rgba(255,138,0,.18)}
.showroom-gallery__thumb img{width:100%;height:100%;object-fit:cover;display:block}
.contacts-map__visual{min-height:230px;background:linear-gradient(135deg,#1f2937,#111827);display:flex;align-items:flex-end;padding:20px;color:#fff}
.contacts-map__visual div{padding:14px;border-radius:14px;background:rgba(255,255,255,.1);backdrop-filter:blur(4px)}
.contacts-map__visual span{display:block;color:#ff8a00;font-weight:900;font-size:13px;margin-bottom:4px}
.contacts-map__visual strong{font-size:18px;line-height:1.35}
.contacts-map__body{padding:20px}.contacts-map__body h2,.contacts-find h2{font-size:22px;font-weight:900;color:#111;margin:0 0 8px}
.contacts-map__body p,.contacts-find p{font-size:15px;line-height:1.75;color:#555;margin:0 0 16px}
.contacts-find{padding:24px}
@media(min-width:768px){
  .contacts-hero{grid-template-columns:minmax(0,1.5fr) minmax(280px,.75fr)}
  .contacts-media-grid{grid-template-columns:minmax(0,1.45fr) minmax(320px,.75fr);gap:22px}
  .contacts-map{position:sticky;top:96px}
}
@media(min-width:1180px){.contacts-hero__copy{padding:34px}.contacts-info article{padding:20px}.showroom-gallery__main{aspect-ratio:16/9}}
@media(max-width:640px){
  .contacts-page{padding:20px 0 44px}
  .contacts-hero__copy{padding:20px;border-radius:16px}
  .contacts-actions{flex-direction:column}.contacts-actions .contacts-btn{width:100%}
  .showroom-gallery__main{aspect-ratio:4/3}
  .showroom-gallery__arrow{width:40px;height:40px}
  .showroom-gallery__thumbs{padding-right:76px;scrollbar-width:none}.showroom-gallery__thumbs::-webkit-scrollbar{display:none}
  .contacts-map__visual{min-height:190px}
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('[data-showroom-gallery]').forEach(function (gallery) {
        var slides = Array.from(gallery.querySelectorAll('[data-showroom-slide]'));
        var thumbs = Array.from(gallery.querySelectorAll('[data-showroom-thumb]'));
        var current = gallery.querySelector('[data-showroom-current]');
        var index = 0;
        var startX = null;

        var show = function (nextIndex) {
            index = (nextIndex + slides.length) % slides.length;
            slides.forEach(function (slide, i) { slide.classList.toggle('is-active', i === index); });
            thumbs.forEach(function (thumb, i) { thumb.classList.toggle('is-active', i === index); });
            if (current) current.textContent = index + 1;
            if (thumbs[index]) thumbs[index].scrollIntoView({ behavior: 'smooth', inline: 'nearest', block: 'nearest' });
        };

        gallery.querySelector('[data-showroom-prev]')?.addEventListener('click', function () { show(index - 1); });
        gallery.querySelector('[data-showroom-next]')?.addEventListener('click', function () { show(index + 1); });
        thumbs.forEach(function (thumb) {
            thumb.addEventListener('click', function () { show(parseInt(thumb.dataset.showroomThumb, 10)); });
        });
        gallery.addEventListener('touchstart', function (event) { startX = event.touches[0].clientX; }, { passive: true });
        gallery.addEventListener('touchend', function (event) {
            if (startX === null) return;
            var diff = event.changedTouches[0].clientX - startX;
            if (Math.abs(diff) > 42) show(index + (diff < 0 ? 1 : -1));
            startX = null;
        }, { passive: true });
    });
});
</script>
@endsection

@section('schema')
<x-schema.breadcrumbs :items="[['name' => 'Контакты', 'url' => route('contacts')]]"/>
@php
    $localBusinessSchema = [
        '@context' => 'https://schema.org',
        '@type' => 'Store',
        'name' => 'Офисные кресла Алматы',
        'url' => route('contacts'),
        'telephone' => $phone,
        'areaServed' => ['Алматы', 'Казахстан'],
        'openingHours' => 'Mo-Su 09:00-21:00',
        'address' => [
            '@type' => 'PostalAddress',
            'streetAddress' => 'ул. Муратбаева 138',
            'addressLocality' => 'Алматы',
            'addressCountry' => 'KZ',
        ],
        'image' => $showroomPhotos[0]['jpg'] ?? asset('img/og-default.jpg'),
    ];
@endphp
<script type="application/ld+json">{!! json_encode($localBusinessSchema, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES|JSON_PRETTY_PRINT) !!}</script>
@endsection
