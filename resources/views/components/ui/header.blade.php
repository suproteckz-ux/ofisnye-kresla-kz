@php
    $phone         = \App\Services\CacheService::setting('phone', '');
    $whatsapp      = \App\Services\CacheService::setting('whatsapp', '');
    $siteName      = \App\Services\CacheService::setting('site_name', 'Офисные кресла Алматы');
    $navCategories = \Illuminate\Support\Facades\Cache::remember('nav.categories', 3600,
        fn() => \App\Models\Category::active()->root()->ordered()->with('children')->limit(6)->get()
    );
@endphp

<header class="site-header bg-white border-b border-stone-100 sticky top-0 z-50 shadow-sm"
        x-data="{ mobileMenu: false, mobileSearch: false, catalogOpen: false }"
        @pageshow.window="mobileMenu=false;mobileSearch=false;catalogOpen=false;if($refs.catalogDropdown)$refs.catalogDropdown.style.display='none'"
        @keydown.escape.window="mobileMenu=false;mobileSearch=false;catalogOpen=false;if($refs.catalogDropdown)$refs.catalogDropdown.style.display='none'">
    <div class="container mx-auto px-4" style="position:relative">

        {{-- ── Верхняя строка ─────────────────────────────── --}}
        <div class="header-row" style="display:flex;align-items:center;justify-content:space-between;height:46px;gap:14px;min-width:0">

            {{-- Логотип --}}
            <a href="{{ route('home') }}"
               style="display:flex;align-items:center;gap:8px;flex-shrink:0;text-decoration:none;min-width:0">
                <div style="width:32px;height:32px;flex-shrink:0;background:#1c1917;border-radius:8px;
                            display:flex;align-items:center;justify-content:center">
                    <svg style="width:18px;height:18px;color:#fff" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M12 3C8 3 5 6 5 10v2c0 1.5.5 2.9 1.3 4H4v2h16v-2h-2.3A7 7 0 0019 12v-2c0-4-3-7-7-7zm0 2a5 5 0 015 5v2a5 5 0 01-10 0v-2a5 5 0 015-5zm-1 13h2v3h-2v-3z"/>
                    </svg>
                </div>
                {{-- Название скрываем на очень узких экранах --}}
                <div class="header-logo-copy" style="min-width:0;overflow:hidden">
                    <div style="font-weight:700;color:#1c1917;font-size:14px;line-height:1.2;
                                white-space:nowrap;overflow:hidden;text-overflow:ellipsis">{{ $siteName }}</div>
                    <div style="font-size:11px;color:#a8a29e;line-height:1">Алматы</div>
                </div>
            </a>

            {{-- Поиск --}}
            <form action="{{ route('search') }}" method="GET"
                  class="header-search"
                  :data-open="mobileSearch ? 'true' : 'false'"
                  style="flex:1;max-width:620px;min-width:160px;display:flex" id="header-search">
                <input type="search" name="q" value="{{ request('q') }}" x-ref="searchInput"
                       placeholder="Поиск кресел, брендов, артикулов..."
                       aria-label="Поиск кресел, брендов и артикулов"
                       style="flex:1;min-width:0;padding:7px 14px;background:#f5f5f4;
                              border:1px solid #e7e5e4;border-radius:10px 0 0 10px;
                              font-size:13px;color:#1c1917;outline:none">
                <button type="submit" aria-label="Открыть поиск"
                        @click="if (window.innerWidth < 768 && !mobileSearch) { $event.preventDefault(); mobileSearch=true; $nextTick(() => $refs.searchInput.focus()) }"
                        style="padding:7px 14px;background:#1c1917;border:none;
                               border-radius:0 10px 10px 0;cursor:pointer;flex-shrink:0">
                    <svg style="width:16px;height:16px;color:#fff" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                </button>
            </form>

            {{-- Контакты --}}
            <div class="header-actions" style="display:flex;align-items:center;gap:8px;flex-shrink:0">
                @if($phone)
                <a href="tel:{{ preg_replace('/\D/', '', $phone) }}"
                   style="display:none;align-items:center;gap:6px;font-size:13px;font-weight:500;color:#57534e;text-decoration:none;white-space:nowrap" id="header-phone">
                    {{ $phone }}
                </a>
                <a href="tel:{{ preg_replace('/\D/', '', $phone) }}"
                   class="header-mobile-phone"
                   aria-label="Позвонить">
                    <svg width="17" height="17" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                        <path d="M22 16.9v3a2 2 0 01-2.2 2 19.8 19.8 0 01-8.6-3.1 19.5 19.5 0 01-6-6A19.8 19.8 0 012.1 4.2 2 2 0 014.1 2h3a2 2 0 012 1.7c.1 1 .4 2 .7 2.9a2 2 0 01-.5 2.1L8.1 9.9a16 16 0 006 6l1.2-1.2a2 2 0 012.1-.5c.9.3 1.9.6 2.9.7a2 2 0 011.7 2z"/>
                    </svg>
                </a>
                @endif
                @if($whatsapp)
                <a href="https://wa.me/{{ $whatsapp }}" target="_blank" rel="noopener"
                   class="header-desktop-whatsapp"
                   style="display:flex;align-items:center;gap:6px;padding:7px 12px;
                          background:#22c55e;color:#fff;font-size:13px;font-weight:600;
                          border-radius:8px;text-decoration:none;white-space:nowrap;flex-shrink:0">
                    <svg style="width:16px;height:16px;flex-shrink:0" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413z"/>
                    </svg>
                    <span id="wa-text" style="display:none">WhatsApp</span>
                </a>
                @endif
                <button type="button" class="header-menu-button"
                        aria-label="Открыть меню"
                        :aria-expanded="mobileMenu ? 'true' : 'false'"
                        @click="mobileMenu=!mobileMenu;mobileSearch=false;catalogOpen=false">
                    <svg width="22" height="22" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                        <path x-show="!mobileMenu" d="M4 6h16M4 12h16M4 18h16"/>
                        <path x-show="mobileMenu" x-cloak d="M6 6l12 12M18 6L6 18"/>
                    </svg>
                </button>
            </div>
        </div>

        {{-- ── Навигация ───────────────────────────────────── --}}
        <nav class="header-nav"
             style="display:flex;align-items:center;gap:3px;padding-bottom:5px"
             :data-mobile-open="mobileMenu ? 'true' : 'false'"
             @click="if (window.innerWidth < 768 && $event.target.closest('a')) mobileMenu=false">

            {{-- Каталог + мегаменю --}}
            <div class="catalog-menu" style="position:relative;flex-shrink:0"
                 @mouseenter="if (window.innerWidth >= 768) catalogOpen=true"
                 @mouseleave="catalogOpen=false;if($refs.catalogDropdown)$refs.catalogDropdown.style.display='none'"
                 @focusout="if(!$el.contains($event.relatedTarget)){catalogOpen=false;if($refs.catalogDropdown)$refs.catalogDropdown.style.display='none'}"
                 @click.outside="catalogOpen=false;if($refs.catalogDropdown)$refs.catalogDropdown.style.display='none'">
                <a href="{{ route('catalog') }}"
                   :aria-expanded="catalogOpen ? 'true' : 'false'"
                   aria-haspopup="true"
                   @focus="if (window.innerWidth >= 768) catalogOpen=true"
                   style="display:flex;align-items:center;gap:4px;padding:6px 12px;
                          font-size:13px;font-weight:500;color:#44403c;
                          white-space:nowrap;text-decoration:none;border-radius:8px"
                   onmouseover="this.style.background='#f5f5f4'"
                   onmouseout="this.style.background='transparent'">
                    Каталог
                    <svg style="width:12px;height:12px;flex-shrink:0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                    </svg>
                </a>
                <div class="catalog-dropdown" x-ref="catalogDropdown" x-cloak
                     :style="{ display: catalogOpen ? 'block' : 'none' }"
                     @click="if($event.target.closest('a')){catalogOpen=false;$refs.catalogDropdown.style.display='none'}"
                     style="display:none;position:absolute;top:calc(100% + 4px);left:0;
                            background:#fff;border:1px solid #e7e5e4;border-radius:12px;
                            box-shadow:0 12px 30px rgba(28,25,23,0.13);z-index:100;
                            padding:7px;min-width:250px;width:250px">
                    @foreach($navCategories as $cat)
                        <a href="{{ $cat->url }}"
                           style="display:block;padding:8px 11px;font-size:13px;font-weight:600;color:#1c1917;
                                  text-decoration:none;border-radius:8px;white-space:nowrap"
                           onmouseover="this.style.background='#fffbeb';this.style.color='#d97706'"
                           onmouseout="this.style.background='transparent';this.style.color='#1c1917'">
                            {{ $cat->name }}
                        </a>
                        @foreach($cat->children->where('is_active', true) as $child)
                        <a href="{{ url('/' . $cat->slug . '/' . $child->slug) }}"
                           style="display:block;padding:7px 11px 7px 18px;font-size:12px;color:#78716c;
                                  text-decoration:none;border-radius:8px;white-space:nowrap"
                           onmouseover="this.style.background='#fffbeb';this.style.color='#d97706'"
                           onmouseout="this.style.background='transparent';this.style.color='#78716c'">
                            {{ $child->name }}
                        </a>
                        @endforeach
                    @endforeach
                </div>
            </div>

            <a href="{{ url('/ofisnye-kresla') }}"
               style="padding:6px 10px;font-size:13px;font-weight:500;color:#78716c;white-space:nowrap;text-decoration:none;border-radius:8px;flex-shrink:0"
               onmouseover="this.style.background='#f5f5f4';this.style.color='#1c1917'"
               onmouseout="this.style.background='transparent';this.style.color='#78716c'">
                Офисные кресла
            </a>

            <a href="{{ route('brands') }}"
               style="padding:6px 10px;font-size:13px;font-weight:500;color:#78716c;
                      white-space:nowrap;text-decoration:none;border-radius:8px;flex-shrink:0"
               onmouseover="this.style.background='#f5f5f4'" onmouseout="this.style.background='transparent'">
                Бренды
            </a>
            <a href="{{ route('blog') }}"
               style="padding:6px 10px;font-size:13px;font-weight:500;color:#78716c;
                      white-space:nowrap;text-decoration:none;border-radius:8px;flex-shrink:0"
               onmouseover="this.style.background='#f5f5f4'" onmouseout="this.style.background='transparent'">
                Блог
            </a>
            <a href="{{ route('promotions') }}"
               style="padding:6px 10px;font-size:13px;font-weight:500;color:#78716c;
                      white-space:nowrap;text-decoration:none;border-radius:8px;flex-shrink:0"
               onmouseover="this.style.background='#f5f5f4'" onmouseout="this.style.background='transparent'">
                Акции
            </a>
            <a href="{{ route('delivery-payment') }}"
               style="padding:6px 10px;font-size:13px;font-weight:500;color:#78716c;
                      white-space:nowrap;text-decoration:none;border-radius:8px;flex-shrink:0"
               onmouseover="this.style.background='#f5f5f4'" onmouseout="this.style.background='transparent'">
                Доставка и оплата
            </a>
            <a href="{{ route('contacts') }}"
               style="padding:6px 10px;font-size:13px;font-weight:500;color:#78716c;
                      white-space:nowrap;text-decoration:none;border-radius:8px;flex-shrink:0"
               onmouseover="this.style.background='#f5f5f4'" onmouseout="this.style.background='transparent'">
                Контакты
            </a>

        </nav>
    </div>
</header>

<style>
/* Header responsive — заменяет Tailwind-классы */
.header-mobile-phone,.header-menu-button{display:none}
@media(max-width:767px){
  .site-header .container{padding-left:14px!important;padding-right:14px!important}
  .header-row{height:58px!important;gap:8px!important}
  .header-logo-copy>div:first-child{max-width:175px;font-size:12px!important}
  .header-logo-copy>div:last-child{display:none}
  .header-actions{order:3;gap:7px!important}
  .header-mobile-phone,.header-menu-button{width:36px;height:36px;display:grid;place-items:center;border:0;border-radius:9px;flex:0 0 auto}
  .header-mobile-phone{background:#22c55e;color:#fff}
  .header-menu-button{background:#fff;color:#1c1917}
  .header-desktop-whatsapp{display:none!important}
  #header-search{order:2;flex:0 0 36px!important;min-width:36px!important;width:36px;max-width:36px!important}
  #header-search input{display:none;padding:8px 11px!important;font-size:12px!important}
  #header-search button{width:36px;height:36px;padding:0!important;display:grid;place-items:center;border-radius:9px!important;background:#fff!important}
  #header-search button svg{color:#1c1917!important;width:18px!important;height:18px!important}
  #header-search[data-open="true"]{position:absolute;left:14px;right:14px;top:55px;z-index:120;width:auto;max-width:none!important;display:flex!important;filter:drop-shadow(0 8px 14px rgba(0,0,0,.12))}
  #header-search[data-open="true"] input{display:block;border-radius:9px 0 0 9px!important;background:#fff!important}
  #header-search[data-open="true"] button{border-radius:0 9px 9px 0!important;background:#1c1917!important}
  #header-search[data-open="true"] button svg{color:#fff!important}
  .header-nav{position:absolute;left:14px;right:14px;top:58px;z-index:110;display:none!important;flex-direction:column;align-items:stretch!important;gap:2px!important;padding:8px!important;background:#fff;border:1px solid #e7e5e4;border-radius:12px;box-shadow:0 12px 30px rgba(28,25,23,.13)}
  .header-nav[data-mobile-open="true"]{display:flex!important}
  .header-nav>a,.header-nav>div>a{display:flex!important;width:100%;padding:10px 12px!important}
  .header-nav>div{width:100%}
  .header-nav>div>div{display:none!important}
}
@media(min-width:768px){
  .header-nav{display:flex}
}
@media(min-width:1024px){
  #header-phone{display:flex!important}
}
@media(min-width:480px){
  #wa-text{display:inline!important}
}
</style>
