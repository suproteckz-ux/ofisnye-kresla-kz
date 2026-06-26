@php
    $phone = \App\Services\CacheService::setting('phone', '+7 778 492 11 13') ?: '+7 778 492 11 13';
    $whatsapp = \App\Services\CacheService::setting('whatsapp', '77784921113') ?: '77784921113';
    $address = \App\Services\CacheService::setting('address', 'г. Алматы, ул. Муратбаева 138') ?: 'г. Алматы, ул. Муратбаева 138';
    $siteName = 'Офисные кресла Алматы';

    $resolveFooterUrl = function (array $categorySlugs = [], array $seoSlugs = []) {
        $category = \App\Models\Category::query()
            ->where('is_active', true)
            ->whereIn('slug', $categorySlugs)
            ->get()
            ->sortBy(fn ($item) => array_search($item->slug, $categorySlugs, true))
            ->first();

        if ($category) {
            return $category->url;
        }

        $seoPage = \App\Models\SeoPage::query()
            ->where('is_active', true)
            ->whereIn('slug', $seoSlugs)
            ->get()
            ->sortBy(fn ($item) => array_search($item->slug, $seoSlugs, true))
            ->first();

        return $seoPage?->url ?? route('catalog');
    };

    $catalogLinks = [
        ['Офисные кресла', $resolveFooterUrl(['ofisnye-kresla'], ['ofisnye-kresla-almaty'])],
        ['Кресла с подголовником', $resolveFooterUrl(['s-podgolovnikom', 'kresla-s-podgolovnikom'], ['ofisnye-kresla-s-podgolovnikom-almaty'])],
        ['Кресла без подголовника', $resolveFooterUrl(['bez-podgolovnika', 'kresla-bez-podgolovnika'], ['ofisnye-kresla-bez-podgolovnika-almaty'])],
        ['Кресла для руководителей', $resolveFooterUrl(['kresla-rukovoditelya', 'rukovoditelya'], ['kresla-rukovoditelia-almaty'])],
    ];
@endphp

<footer class="site-footer">
    <div class="site-footer__inner">
        <div class="site-footer__grid">
            <div class="site-footer__brand">
                <a href="{{ route('home') }}" class="site-footer__logo" aria-label="{{ $siteName }}">
                    <span class="site-footer__logo-mark">
                        <svg width="17" height="17" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path d="M12 4C9 4 7 6.5 7 9.5v2c0 1.2.4 2.3 1 3.1H4v2h16v-2h-4c.6-.8 1-1.9 1-3.1v-2C17 6.5 15 4 12 4z"/>
                            <rect x="11" y="16.5" width="2" height="3" rx="1"/>
                        </svg>
                    </span>
                    <span>{{ $siteName }}</span>
                </a>
                <p>Продажа офисных кресел в Алматы и Казахстане.</p>
            </div>

            <nav class="site-footer__col" aria-label="Каталог в футере">
                <div class="site-footer__title">Каталог</div>
                @foreach($catalogLinks as [$label, $url])
                    <a href="{{ $url }}">{{ $label }}</a>
                @endforeach
            </nav>

            <nav class="site-footer__col" aria-label="Покупателям">
                <div class="site-footer__title">Покупателям</div>
                <a href="{{ route('delivery-payment') }}#delivery">Доставка</a>
                <a href="{{ route('delivery-payment') }}#payment">Оплата</a>
                <a href="{{ route('delivery-payment') }}#warranty">Гарантия</a>
                <a href="{{ route('contacts') }}">Контакты</a>
            </nav>

            <div class="site-footer__contacts">
                <a href="{{ route('contacts') }}" class="site-footer__title">Контакты</a>
                <p>{{ $address }}</p>
                <a href="tel:{{ preg_replace('/\D/', '', $phone) }}" class="site-footer__phone">{{ $phone }}</a>
                <a href="https://wa.me/{{ preg_replace('/\D/', '', $whatsapp) }}" target="_blank" rel="noopener" class="site-footer__wa">
                    <svg width="15" height="15" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413z"/>
                    </svg>
                    WhatsApp
                </a>
            </div>
        </div>

        <div class="site-footer__bottom">
            © {{ date('Y') }} {{ $siteName }}. Все права защищены.
        </div>
    </div>
</footer>

<style>
.site-footer{background:#111820;color:#b8c0c8;margin-top:auto}
.site-footer__inner{max-width:1280px;margin:0 auto;padding:52px 28px 24px}
.site-footer__grid{display:grid;grid-template-columns:1fr;gap:34px;margin-bottom:36px}
.site-footer__logo{display:inline-flex;align-items:center;gap:11px;color:#fff;font-weight:850;font-size:18px;text-decoration:none;margin-bottom:14px}
.site-footer__logo-mark{width:36px;height:36px;border-radius:10px;background:#ff8a00;color:#fff;display:grid;place-items:center;flex:0 0 36px}
.site-footer__brand p,.site-footer__contacts p{font-size:14px;line-height:1.75;color:#aab3bc;margin:0;max-width:290px}
.site-footer__title{display:block;color:#fff;font-weight:800;font-size:15px;margin-bottom:15px;text-decoration:none}
.site-footer__col a{display:block;color:#b8c0c8;font-size:14px;text-decoration:none;margin-bottom:10px;line-height:1.5;transition:color .15s}
.site-footer__col a:hover,.site-footer__contacts a:hover{color:#fff}
.site-footer__phone{display:inline-block;color:#fff;font-weight:800;font-size:15px;text-decoration:none;margin:12px 0 14px}
.site-footer__wa{display:inline-flex;align-items:center;gap:8px;padding:11px 17px;border-radius:10px;background:#22c55e;color:#fff!important;font-size:14px;font-weight:850;text-decoration:none;line-height:1}
.site-footer__wa:hover{background:#16a34a}
.site-footer__bottom{border-top:1px solid rgba(255,255,255,.09);padding-top:18px;color:#7f8a94;font-size:13px}
@media(min-width:720px){.site-footer__grid{grid-template-columns:1.25fr 1fr;gap:36px 42px}.site-footer__contacts{grid-column:auto}}
@media(min-width:1024px){.site-footer__grid{grid-template-columns:1.55fr 1fr 1fr 1.2fr;gap:46px}}
@media(max-width:719px){.site-footer__inner{padding:40px 20px 22px}.site-footer__grid{grid-template-columns:1fr;gap:26px}.site-footer__wa{width:auto}.site-footer__bottom{line-height:1.6}}
</style>
