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
                <p>Приходите в шоурум, чтобы протестировать кресла, сравнить модели и выбрать удобный вариант для дома или офиса.</p>
                <div class="contacts-actions">
                    <a href="https://wa.me/{{ $cleanWhatsapp }}?text={{ $waText }}" target="_blank" rel="noopener" class="contacts-btn contacts-btn--wa" data-analytics-location="contacts">
                        WhatsApp
                    </a>
                    <a href="{{ $routeUrl }}" target="_blank" rel="noopener" class="contacts-btn contacts-btn--route">
                        Построить маршрут
                    </a>
                </div>
            </div>

            <aside class="showroom-card">
                <h2>Шоурум в Алматы</h2>
                <div class="showroom-card__list">
                    <div class="showroom-card__item">
                        <span class="showroom-card__icon">
                            <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M22 16.9v3a2 2 0 01-2.2 2 19.8 19.8 0 01-8.6-3.1 19.5 19.5 0 01-6-6A19.8 19.8 0 012.1 4.2 2 2 0 014.1 2h3a2 2 0 012 1.7c.1 1 .4 2 .7 2.9a2 2 0 01-.5 2.1L8.1 9.9a16 16 0 006 6l1.2-1.2a2 2 0 012.1-.5c.9.3 1.9.6 2.9.7a2 2 0 011.7 2z"/></svg>
                        </span>
                        <div>
                            <strong><a href="tel:{{ $cleanPhone }}" data-analytics-location="contacts" data-phone="{{ $cleanPhone }}">{{ $phone }}</a></strong>
                            <small>WhatsApp, звонки</small>
                        </div>
                    </div>
                    <div class="showroom-card__item">
                        <span class="showroom-card__icon">
                            <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M12 21s7-4.6 7-11a7 7 0 10-14 0c0 6.4 7 11 7 11z"/><circle cx="12" cy="10" r="2.4"/></svg>
                        </span>
                        <div>
                            <strong>{{ $address }}</strong>
                            <small>Ориентир: между ул. Курмангазы и ул. Толе Би</small>
                        </div>
                    </div>
                    <div class="showroom-card__item">
                        <span class="showroom-card__icon">
                            <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/></svg>
                        </span>
                        <div>
                            <strong>Режим работы</strong>
                            <small>Пн–Пт: 09:00–18:00<br>Сб: 11:00–16:00<br>Вс: выходной</small>
                        </div>
                    </div>
                </div>
                <div class="showroom-card__actions">
                    <a href="https://wa.me/{{ $cleanWhatsapp }}?text={{ $waText }}" target="_blank" rel="noopener" class="contacts-btn contacts-btn--wa" data-analytics-location="contacts">WhatsApp</a>
                    <a href="{{ $routeUrl }}" target="_blank" rel="noopener" class="contacts-btn contacts-btn--route">Построить маршрут</a>
                </div>
            </aside>
        </div>

        <div class="contacts-section-title">
            <h2>Фото шоурума</h2>
        </div>

        <div class="contacts-media-grid">
            <section class="showroom-gallery" data-showroom-gallery>
                <div class="showroom-gallery__layout">
                    <div class="showroom-gallery__main">
                        @foreach($showroomPhotos as $index => $photo)
                        <picture class="showroom-gallery__slide {{ $index === 0 ? 'is-active' : '' }}" data-showroom-slide>
                            <source srcset="{{ $photo['webp'] }}" type="image/webp">
                            <img src="{{ $photo['jpg'] }}"
                                 alt="{{ $photo['alt'] }}"
                                 class="{{ $index === 0 ? 'showroom-gallery__image--top' : '' }}"
                                 width="1600"
                                 height="1200"
                                 loading="{{ $index === 0 ? 'eager' : 'lazy' }}"
                                 fetchpriority="{{ $index === 0 ? 'high' : 'auto' }}"
                                 decoding="async">
                        </picture>
                        @endforeach

                        <button type="button" class="showroom-gallery__arrow showroom-gallery__arrow--prev" data-showroom-prev aria-label="Предыдущее фото">
                            <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M15 18l-6-6 6-6"/></svg>
                        </button>
                        <button type="button" class="showroom-gallery__arrow showroom-gallery__arrow--next" data-showroom-next aria-label="Следующее фото">
                            <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M9 18l6-6-6-6"/></svg>
                        </button>
                        <div class="showroom-gallery__count"><span data-showroom-current>1</span> / {{ count($showroomPhotos) }}</div>
                    </div>

                    <div class="showroom-gallery__side">
                        @foreach(array_slice($showroomPhotos, 1, 2) as $sidePhoto)
                        <picture>
                            <source srcset="{{ $sidePhoto['webp'] }}" type="image/webp">
                            <img src="{{ $sidePhoto['jpg'] }}" alt="{{ $sidePhoto['alt'] }}" width="420" height="210" loading="lazy" decoding="async">
                        </picture>
                        @endforeach
                    </div>
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
                <div class="contacts-map__visual" aria-hidden="true">
                    <span class="contacts-map__badge">2GIS</span>
                    <span class="contacts-map__road contacts-map__road--one"></span>
                    <span class="contacts-map__road contacts-map__road--two"></span>
                    <span class="contacts-map__road contacts-map__road--three"></span>
                    <span class="contacts-map__pin">
                        <svg width="28" height="28" fill="currentColor" viewBox="0 0 24 24"><path d="M12 22s7-5.2 7-12a7 7 0 10-14 0c0 6.8 7 12 7 12z"/><circle cx="12" cy="10" r="2.6" fill="#fff"/></svg>
                    </span>
                    <strong>{{ $address }}</strong>
                </div>
                <div class="contacts-map__body">
                    <h2>Карта проезда</h2>
                    <p>Откройте маршрут в 2GIS, чтобы построить удобный путь до шоурума.</p>
                    <a href="{{ $routeUrl }}" target="_blank" rel="noopener" class="contacts-btn contacts-btn--route">Открыть в 2GIS</a>
                </div>
            </aside>
        </div>

        <section class="contacts-find">
            <div class="contacts-find__icon">
                <svg width="26" height="26" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M12 21s7-4.6 7-11a7 7 0 10-14 0c0 6.4 7 11 7 11z"/><circle cx="12" cy="10" r="2.4"/></svg>
            </div>
            <div>
                <h2>Как нас найти</h2>
                <p>Шоурум находится по адресу г. Алматы, ул. Муратбаева 138. В наличии офисные кресла для дома, офиса, руководителей и сотрудников. Можно приехать, посидеть на разных моделях и выбрать подходящий вариант.</p>
                <a href="{{ $routeUrl }}" target="_blank" rel="noopener" class="contacts-btn contacts-btn--route">Построить маршрут</a>
            </div>
        </section>
    </div>
</section>

<style>
.contacts-page{padding:22px 0 56px;background:#f7f7f8}
.contacts-hero{display:grid;grid-template-columns:1fr;gap:16px;margin-bottom:22px}
.contacts-hero__copy,.showroom-card,.showroom-gallery,.contacts-map,.contacts-find{border:1px solid #e9e9ea;border-radius:20px;background:#fff;box-shadow:0 12px 34px rgba(17,17,17,.055)}
.contacts-hero__copy{padding:26px}
.contacts-eyebrow{display:inline-flex;margin-bottom:10px;color:#ff8a00;font-size:12px;font-weight:900;text-transform:uppercase}
.contacts-hero h1{font-size:clamp(2rem,4vw,3rem);line-height:1.08;font-weight:900;color:#111;margin:0 0 12px;max-width:780px}
.contacts-hero p{font-size:16px;line-height:1.62;color:#52525b;margin:0;max-width:640px}
.contacts-actions,.showroom-card__actions{display:flex;gap:10px;flex-wrap:wrap;margin-top:20px}
.contacts-btn{display:inline-flex;align-items:center;justify-content:center;min-height:42px;padding:11px 17px;border-radius:11px;font-size:14px;font-weight:850;text-decoration:none;border:1px solid transparent;transition:transform .18s,box-shadow .18s,background .18s}
.contacts-btn--wa{background:#22c55e;color:#fff}.contacts-btn--wa:hover{background:#16a34a;color:#fff}
.contacts-btn--route{background:#ff8a00;color:#fff}.contacts-btn--route:hover{background:#f97316;color:#fff}
.showroom-card{padding:22px}
.showroom-card h2{font-size:21px;font-weight:900;color:#111;margin:0 0 18px}
.showroom-card__list{display:grid;gap:16px}
.showroom-card__item{display:grid;grid-template-columns:34px 1fr;gap:12px;align-items:start}
.showroom-card__icon{width:34px;height:34px;border-radius:12px;background:#fff7ed;color:#ff8a00;display:grid;place-items:center}
.showroom-card strong,.showroom-card a{font-size:15px;line-height:1.35;font-weight:900;color:#111;text-decoration:none}
.showroom-card small{display:block;margin-top:4px;font-size:12px;line-height:1.45;color:#666}
.showroom-card__actions .contacts-btn{flex:1;min-width:150px}
.contacts-section-title{margin:8px 0 10px}.contacts-section-title h2{font-size:20px;font-weight:900;color:#111;margin:0}
.contacts-media-grid{display:grid;grid-template-columns:1fr;gap:16px;align-items:stretch;margin-bottom:16px}
.showroom-gallery{padding:14px;overflow:hidden}
.showroom-gallery__layout{display:grid;grid-template-columns:1fr;gap:12px}
.showroom-gallery__main{position:relative;height:min(45vw,420px);min-height:300px;max-height:420px;background:#f5f5f5;border-radius:16px;overflow:hidden}
.showroom-gallery__slide{position:absolute;inset:0;opacity:0;transform:scale(1.01);transition:opacity .22s ease,transform .22s ease}
.showroom-gallery__slide.is-active{opacity:1;transform:scale(1)}
.showroom-gallery__slide img{width:100%;height:100%;object-fit:cover;object-position:center;display:block}
.showroom-gallery__slide img.showroom-gallery__image--top{object-position:center top}
.showroom-gallery__arrow{position:absolute;top:50%;z-index:2;transform:translateY(-50%);width:34px;height:34px;border:0;border-radius:999px;background:rgba(255,255,255,.9);color:#111;display:grid;place-items:center;box-shadow:0 5px 16px rgba(0,0,0,.13);cursor:pointer}
.showroom-gallery__arrow--prev{left:12px}.showroom-gallery__arrow--next{right:12px}
.showroom-gallery__count{position:absolute;right:12px;top:12px;z-index:2;padding:4px 9px;border-radius:99px;background:rgba(0,0,0,.5);color:#fff;font-size:12px;font-weight:850}
.showroom-gallery__thumbs{display:flex;gap:8px;padding-top:12px;overflow-x:auto;overscroll-behavior-x:contain}
.showroom-gallery__thumb{width:78px;height:54px;flex:0 0 78px;border:2px solid transparent;border-radius:11px;padding:0;overflow:hidden;background:#f8f8f8;cursor:pointer}
.showroom-gallery__thumb.is-active{border-color:#ff8a00;box-shadow:0 0 0 2px rgba(255,138,0,.2)}
.showroom-gallery__thumb img{width:100%;height:100%;object-fit:cover;display:block}
.showroom-gallery__side{display:none;gap:12px}
.showroom-gallery__side picture{display:block;height:calc((min(45vw,420px) - 12px) / 2);max-height:204px;min-height:144px;border-radius:14px;overflow:hidden;background:#f5f5f5}
.showroom-gallery__side img{width:100%;height:100%;object-fit:cover;display:block}
.contacts-map{display:flex;flex-direction:column;overflow:hidden}
.contacts-map__visual{position:relative;min-height:250px;flex:1;background:#faf7ef;overflow:hidden;border-bottom:1px solid #eee;display:flex;align-items:center;justify-content:center;padding:20px;color:#111}
.contacts-map__visual::before{content:"";position:absolute;inset:-20px;background-image:linear-gradient(90deg,rgba(180,180,180,.22) 1px,transparent 1px),linear-gradient(rgba(180,180,180,.22) 1px,transparent 1px);background-size:42px 42px;transform:rotate(-8deg) scale(1.2)}
.contacts-map__road{position:absolute;height:10px;border-radius:99px;background:#fff;box-shadow:0 0 0 1px #eee}
.contacts-map__road--one{width:115%;top:42%;left:-8%;transform:rotate(-18deg)}
.contacts-map__road--two{width:95%;top:62%;left:4%;transform:rotate(12deg)}
.contacts-map__road--three{width:65%;top:18%;left:22%;transform:rotate(28deg)}
.contacts-map__pin{position:relative;z-index:2;color:#ff8a00;filter:drop-shadow(0 8px 16px rgba(255,138,0,.25))}
.contacts-map__badge{position:absolute;right:16px;top:16px;z-index:2;padding:5px 9px;border-radius:999px;background:#fff;color:#16a34a;font-size:11px;font-weight:900;box-shadow:0 4px 16px rgba(17,17,17,.08)}
.contacts-map__visual strong{position:absolute;left:18px;right:18px;bottom:16px;z-index:2;padding:11px 12px;border-radius:13px;background:rgba(255,255,255,.92);font-size:14px;line-height:1.35;box-shadow:0 6px 20px rgba(17,17,17,.08)}
.contacts-map__body{padding:18px}.contacts-map__body h2,.contacts-find h2{font-size:20px;font-weight:900;color:#111;margin:0 0 8px}
.contacts-map__body p,.contacts-find p{font-size:14px;line-height:1.65;color:#555;margin:0 0 14px}
.contacts-find{display:grid;grid-template-columns:48px 1fr;gap:14px;padding:22px;border-left:5px solid #ff8a00}
.contacts-find__icon{width:48px;height:48px;border-radius:16px;background:#fff7ed;color:#ff8a00;display:grid;place-items:center}
@media(min-width:768px){
  .contacts-hero{grid-template-columns:minmax(0,1.35fr) minmax(350px,.75fr);gap:18px;align-items:stretch}
  .contacts-hero__copy{display:flex;flex-direction:column;justify-content:center;min-height:300px}
  .contacts-media-grid{grid-template-columns:minmax(0,2fr) minmax(320px,1fr);gap:18px}
  .showroom-gallery__layout{grid-template-columns:minmax(0,1.45fr) minmax(220px,.55fr)}
  .showroom-gallery__side{display:grid}
}
@media(min-width:1180px){.contacts-hero__copy{padding:32px}.showroom-card{padding:24px}.contacts-map__visual{min-height:288px}}
@media(max-width:640px){
  .contacts-page{padding:16px 0 42px}
  .contacts-hero__copy,.showroom-card{padding:18px;border-radius:17px}
  .contacts-hero h1{font-size:28px;line-height:1.12}
  .contacts-hero p{font-size:14px}
  .contacts-actions,.showroom-card__actions{flex-direction:column}
  .contacts-actions .contacts-btn,.showroom-card__actions .contacts-btn{width:100%}
  .showroom-card__item{grid-template-columns:30px 1fr;gap:10px}
  .showroom-card__icon{width:30px;height:30px;border-radius:10px}
  .showroom-gallery{padding:10px;border-radius:17px}
  .showroom-gallery__main{height:min(76vw,320px);min-height:245px;max-height:320px;border-radius:14px}
  .showroom-gallery__arrow{width:32px;height:32px}
  .showroom-gallery__thumbs{padding-right:76px;scrollbar-width:none}.showroom-gallery__thumbs::-webkit-scrollbar{display:none}
  .contacts-map__visual{min-height:190px}
  .contacts-find{grid-template-columns:1fr;padding:18px}
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
        'openingHours' => 'Mo-Fr 09:00-18:00, Sa 11:00-16:00',
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
