@php
    $phone    = \App\Services\CacheService::setting('phone', '');
    $whatsapp = \App\Services\CacheService::setting('whatsapp', '');
    $address  = \App\Services\CacheService::setting('address', 'г. Алматы, Казахстан');
    $siteName = config('app.name', 'Офисные кресла Алматы');
    $footerCats = \Illuminate\Support\Facades\Cache::remember('footer.cats', 3600,
        fn() => \App\Models\Category::active()->root()->ordered()->limit(5)->get()
    );
@endphp

<footer style="background:#111;color:#aaa;margin-top:auto">
    <div style="max-width:1280px;margin:0 auto;padding:40px 24px 20px">
        <div class="footer-cols" style="margin-bottom:32px">

            {{-- Колонка 1 --}}
            <div>
                <div style="display:flex;align-items:center;gap:9px;margin-bottom:10px">
                    <div style="width:30px;height:30px;background:#ff8a00;border-radius:7px;
                                display:flex;align-items:center;justify-content:center;flex-shrink:0">
                        <svg width="16" height="16" fill="#fff" viewBox="0 0 24 24">
                            <path d="M12 4C9 4 7 6.5 7 9.5v2c0 1.2.4 2.3 1 3.1H4v2h16v-2h-4c.6-.8 1-1.9 1-3.1v-2C17 6.5 15 4 12 4z"/>
                            <rect x="11" y="16.5" width="2" height="3" rx="1"/>
                        </svg>
                    </div>
                    <span style="color:#fff;font-weight:700;font-size:15px">{{ $siteName }}</span>
                </div>
                <p style="font-size:13px;line-height:1.7;color:#777;max-width:220px">
                    Продажа офисных кресел в Алматы и Казахстане
                </p>
            </div>

            {{-- Колонка 2: Каталог --}}
            <div>
                <div style="color:#fff;font-weight:600;font-size:14px;margin-bottom:12px">Каталог</div>
                @forelse($footerCats as $cat)
                <a href="{{ $cat->url }}"
                   style="display:block;font-size:13px;color:#777;margin-bottom:8px;
                          text-decoration:none;transition:color .15s"
                   onmouseover="this.style.color='#fff'"
                   onmouseout="this.style.color='#777'">{{ $cat->name }}</a>
                @empty
                <a href="{{ route('catalog') }}" style="display:block;font-size:13px;color:#777;margin-bottom:8px;text-decoration:none">Офисные кресла</a>
                <a href="{{ route('catalog.category','kresla-rukovoditelya') }}" style="display:block;font-size:13px;color:#777;margin-bottom:8px;text-decoration:none">Кресла руководителя</a>
                <a href="{{ route('catalog.category','ergonomichnye-kresla') }}" style="display:block;font-size:13px;color:#777;text-decoration:none">Эргономичные кресла</a>
                @endforelse
            </div>

            {{-- Колонка 3: Покупателям --}}
            <div>
                <div style="color:#fff;font-weight:600;font-size:14px;margin-bottom:12px">Покупателям</div>
                <span style="display:block;font-size:13px;color:#777;margin-bottom:8px">Доставка</span>
                <span style="display:block;font-size:13px;color:#777;margin-bottom:8px">Оплата</span>
                <span style="display:block;font-size:13px;color:#777">Гарантия</span>
            </div>

            {{-- Колонка 4: Контакты --}}
            <div>
                <div style="color:#fff;font-weight:600;font-size:14px;margin-bottom:12px">Контакты</div>
                @if($address)
                <p style="font-size:13px;color:#777;margin-bottom:8px">{{ $address }}</p>
                @endif
                @if($phone)
                <a href="tel:{{ preg_replace('/\D/','',$phone) }}"
                   style="display:block;font-size:14px;color:#fff;font-weight:600;margin-bottom:12px;text-decoration:none">
                    {{ $phone }}
                </a>
                @endif
                @if($whatsapp)
                <a href="https://wa.me/{{ $whatsapp }}" target="_blank" rel="noopener"
                   style="display:inline-flex;align-items:center;gap:7px;padding:8px 16px;
                          background:#22c55e;color:#fff;font-weight:600;font-size:13px;
                          border-radius:8px;text-decoration:none">
                    <svg width="15" height="15" fill="#fff" viewBox="0 0 24 24">
                        <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413z"/>
                    </svg>
                    WhatsApp
                </a>
                @endif
            </div>
        </div>

        <div style="border-top:1px solid #222;padding-top:16px;font-size:12px;color:#555">
            © {{ date('Y') }} {{ $siteName }}. Все права защищены.
        </div>
    </div>
</footer>

<style>
.footer-cols{display:grid;grid-template-columns:1fr;gap:28px}
@media(max-width:639px){
  footer>div{padding:28px 18px 17px!important}
  .footer-cols{grid-template-columns:repeat(2,minmax(0,1fr));gap:24px 18px;margin-bottom:24px!important}
  .footer-cols>div:first-child,.footer-cols>div:last-child{grid-column:1/-1}
}
@media(min-width:640px){.footer-cols{grid-template-columns:repeat(2,1fr);gap:32px}}
@media(min-width:1024px){.footer-cols{grid-template-columns:2fr 1fr 1fr 1.5fr;gap:40px}}
</style>
