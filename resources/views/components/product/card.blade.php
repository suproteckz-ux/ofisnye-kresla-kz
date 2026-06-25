@php
if (is_array($product)) {
    $product = \App\Models\Product::query()
        ->with(['brand', 'category:id,name,slug,parent_id', 'category.parent:id,slug'])
        ->when(!empty($product['id'] ?? null), fn($q) => $q->where('id', $product['id']))
        ->when(empty($product['id'] ?? null) && !empty($product['slug'] ?? null), fn($q) => $q->where('slug', $product['slug']))
        ->first();
}

if (!$product) {
    return;
}

$hasDiscount = method_exists($product, 'hasDiscount')
    ? $product->hasDiscount()
    : (!empty($product->old_price) && $product->old_price > $product->price);

$wa = \App\Services\CacheService::setting('whatsapp', '');
$waMsg = urlencode('Хочу заказать: ' . ($product->name ?? '') . ' — ' . config('app.name'));
$image = ltrim((string) ($product->main_image ?? ''), '/');
$webp = ltrim((string) ($product->main_image_webp ?? ''), '/');
$disk = \Illuminate\Support\Facades\Storage::disk('public');
$srcset = '';
$thumb = $image ? asset('storage/' . $image) : asset('img/no-photo.svg');

if ($webp !== '') {
    $directory = trim(dirname($webp), '.\\/');
    $name = pathinfo($webp, PATHINFO_FILENAME);
    $items = [];

    foreach ([320, 640, 960] as $width) {
        $variant = ($directory !== '' ? $directory . '/' : '') . "{$name}-{$width}.webp";
        if ($disk->exists($variant)) {
            $items[] = asset('storage/' . $variant) . " {$width}w";
        }
    }

    $thumbPath = ($directory !== '' ? $directory . '/' : '') . "{$name}-thumb.webp";
    if ($disk->exists($thumbPath)) {
        $thumb = asset('storage/' . $thumbPath);
    }

    $srcset = implode(', ', $items);
}
@endphp

@once
<style>
.product-card{position:relative;background:#fff;border-radius:18px;border:1px solid #ececec;overflow:hidden;display:flex;flex-direction:column;height:100%;box-shadow:0 8px 24px rgba(17,17,17,.04);transition:transform .22s ease,border-color .22s ease,box-shadow .22s ease}
.product-card:hover{transform:translateY(-4px);border-color:#ffe0b5;box-shadow:0 18px 42px rgba(17,17,17,.11)}
.product-card__media{display:block;position:relative;background:#fbfbfb;overflow:hidden;flex-shrink:0;height:220px}
.product-card__media img{width:100%;height:100%;object-fit:contain;padding:16px;position:absolute;inset:0;transition:transform .28s ease}
.product-card:hover .product-card__media img{transform:scale(1.045)}
.product-card__badges{position:absolute;top:10px;left:10px;display:flex;flex-direction:column;gap:5px;z-index:2}
.product-card__badge{font-size:10px;font-weight:800;padding:3px 8px;border-radius:999px;color:#fff}
.product-card__badge--new{background:#14b8a6}.product-card__badge--hit{background:#8b5cf6}.product-card__badge--sale{background:#ff5a1f}
.product-card__tools{position:absolute;top:10px;right:10px;display:flex;flex-direction:column;gap:7px;z-index:3}
.product-card__icon{width:34px;height:34px;border:1px solid #e8e8e8;border-radius:50%;background:rgba(255,255,255,.92);color:#64748b;display:grid;place-items:center;cursor:pointer;transition:color .18s,border-color .18s,background .18s}
.product-card__icon:hover,.product-card__icon.is-active{color:#ff8a00;border-color:#ff8a00;background:#fff7ed}
.product-card__quick{position:absolute;right:10px;top:172px;z-index:3;width:38px;height:38px;border:0;border-radius:50%;background:rgba(17,17,17,.58);color:#fff;display:grid;place-items:center;cursor:pointer;transition:background .18s,transform .18s}
.product-card__quick:hover{background:#111;transform:scale(1.04)}
.product-card__stock{position:absolute;left:10px;bottom:10px;z-index:2;font-size:10px;font-weight:700;padding:4px 9px;background:#dcfce7;color:#16a34a;border-radius:999px}
.product-card__body{padding:16px;display:flex;flex-direction:column;flex:1;min-width:0}
.product-card__brand{font-size:11px;color:#d97706;font-weight:800;margin-bottom:5px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;text-transform:uppercase}
.product-card__title{font-size:14px;font-weight:700;color:#111;line-height:1.38;margin-bottom:12px;min-height:39px;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden;text-decoration:none}
.product-card__price{display:flex;align-items:baseline;gap:8px;flex-wrap:wrap;margin-bottom:13px}
.product-card__price strong{font-size:18px;font-weight:900;color:#111}.product-card__price del{font-size:12px;color:#aaa}
.product-card__actions{display:grid;grid-template-columns:1fr auto;gap:8px;margin-top:auto;align-items:center}
.product-card__kaspi{display:flex;align-items:center;justify-content:center;min-height:38px;border-radius:10px;background:#ff4b32;color:#fff;font-size:12px;font-weight:850;text-decoration:none}
.product-card__wa{width:38px;height:38px;border-radius:50%;background:#22c55e;color:#fff;display:grid;place-items:center;text-decoration:none}
.product-card__details{grid-column:1/-1;display:flex;align-items:center;justify-content:center;min-height:34px;border-radius:9px;background:#f7f7f7;color:#444;font-size:12px;font-weight:700;text-decoration:none}
.product-quick[hidden]{display:none!important}
.product-quick{position:fixed;inset:0;z-index:9998;background:rgba(0,0,0,.52);display:grid;place-items:center;padding:18px}
.product-quick__box{width:min(720px,100%);background:#fff;border-radius:18px;box-shadow:0 28px 80px rgba(0,0,0,.28);display:grid;grid-template-columns:260px 1fr;gap:20px;padding:18px;position:relative}
.product-quick__close{position:absolute;top:10px;right:10px;width:36px;height:36px;border:0;border-radius:50%;background:#f3f3f3;font-size:24px;line-height:1}
.product-quick__image{background:#f8f8f8;border-radius:14px;aspect-ratio:1;display:grid;place-items:center;overflow:hidden}
.product-quick__image img{width:100%;height:100%;object-fit:contain;padding:16px}
@media(max-width:640px){
  .product-card__media{height:170px}.product-card__quick{top:134px}.product-card__body{padding:12px}.product-card__title{font-size:12px;min-height:34px}.product-card__price strong{font-size:15px}
  .product-card__actions{grid-template-columns:1fr 36px}.product-card__kaspi{font-size:11px;min-height:36px}.product-card__wa{width:36px;height:36px}
  .product-quick__box{grid-template-columns:1fr;max-height:92vh;overflow:auto}.product-quick__image{max-height:280px}
}
</style>
@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    var readSet = function (key) {
        try { return JSON.parse(localStorage.getItem(key) || '[]'); } catch (e) { return []; }
    };
    var writeSet = function (key, values) { localStorage.setItem(key, JSON.stringify(values)); };
    var toggleStored = function (button, key) {
        var id = button.getAttribute('data-product-id');
        var values = readSet(key);
        var exists = values.indexOf(id) !== -1;
        values = exists ? values.filter(function (item) { return item !== id; }) : values.concat([id]);
        writeSet(key, values);
        button.classList.toggle('is-active', !exists);
    };

    document.querySelectorAll('[data-product-card]').forEach(function (card) {
        ['favorite', 'compare'].forEach(function (type) {
            var button = card.querySelector('[data-' + type + ']');
            if (!button) return;
            button.classList.toggle('is-active', readSet('product_' + type).indexOf(button.getAttribute('data-product-id')) !== -1);
        });
    });

    document.addEventListener('click', function (event) {
        var favorite = event.target.closest('[data-favorite]');
        if (favorite) { event.preventDefault(); toggleStored(favorite, 'product_favorite'); return; }

        var compare = event.target.closest('[data-compare]');
        if (compare) { event.preventDefault(); toggleStored(compare, 'product_compare'); return; }

        var quick = event.target.closest('[data-quick-view]');
        if (quick) {
            event.preventDefault();
            var modal = document.querySelector('[data-quick-modal="' + quick.getAttribute('data-product-id') + '"]');
            if (modal) { modal.removeAttribute('hidden'); document.body.style.overflow = 'hidden'; }
            return;
        }

        var close = event.target.closest('[data-quick-close]');
        if (close || event.target.matches('[data-quick-modal]')) {
            var openModal = event.target.closest('[data-quick-modal]') || document.querySelector('[data-quick-modal]:not([hidden])');
            if (openModal) { openModal.setAttribute('hidden', 'hidden'); document.body.style.overflow = ''; }
        }
    });

    document.addEventListener('keydown', function (event) {
        if (event.key !== 'Escape') return;
        document.querySelectorAll('[data-quick-modal]:not([hidden])').forEach(function (modal) {
            modal.setAttribute('hidden', 'hidden');
            document.body.style.overflow = '';
        });
    });
});
</script>
@endpush
@endonce

<article class="product-card" data-product-card data-product-id="{{ $product->id }}">
    <a href="{{ $product->url }}" class="product-card__media card-img-wrap">
        @if($image)
        <picture>
            @if($webp)
            <source srcset="{{ $srcset ?: asset('storage/'.$webp) }}" type="image/webp" sizes="(max-width: 640px) 45vw, (max-width: 1024px) 30vw, 240px">
            @endif
            <img src="{{ asset('storage/'.$image) }}"
                 alt="{{ $product->main_image_alt ?? $product->name }}"
                 loading="lazy"
                 decoding="async"
                 width="320"
                 height="320"
                 onerror="this.onerror=null;this.src='{{ asset('img/no-photo.svg') }}';this.style.padding='28px'">
        </picture>
        @else
        <img src="{{ asset('img/no-photo.svg') }}" alt="Изображение товара временно отсутствует" loading="lazy" decoding="async" width="160" height="160" style="padding:42px">
        @endif

        @if(!empty($product->is_new) || !empty($product->is_hit) || $hasDiscount)
        <div class="product-card__badges">
            @if(!empty($product->is_new))<span class="product-card__badge product-card__badge--new">Новинка</span>@endif
            @if(!empty($product->is_hit))<span class="product-card__badge product-card__badge--hit">Хит</span>@endif
            @if($hasDiscount)<span class="product-card__badge product-card__badge--sale">-{{ $product->discount_percent }}%</span>@endif
        </div>
        @endif

        <span class="product-card__stock">{{ ($product->in_stock ?? true) ? 'В наличии' : 'Нет в наличии' }}</span>
    </a>

    <div class="product-card__tools">
        <button type="button" class="product-card__icon" data-favorite data-product-id="{{ $product->id }}" aria-label="Добавить в избранное">
            <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M20.8 4.6a5.5 5.5 0 00-7.8 0L12 5.7l-1-1.1a5.5 5.5 0 00-7.8 7.8l1 1L12 21l7.8-7.6 1-1a5.5 5.5 0 000-7.8z"/></svg>
        </button>
        <button type="button" class="product-card__icon" data-compare data-product-id="{{ $product->id }}" aria-label="Добавить к сравнению">
            <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M8 7h12M8 12h12M8 17h12M4 7h.01M4 12h.01M4 17h.01"/></svg>
        </button>
    </div>
    <button type="button" class="product-card__quick" data-quick-view data-product-id="{{ $product->id }}" aria-label="Быстрый просмотр">
        <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M2 12s3.5-6 10-6 10 6 10 6-3.5 6-10 6-10-6-10-6z"/><circle cx="12" cy="12" r="3"/></svg>
    </button>

    <div class="product-card__body product-card-body">
        @if(!empty($product->brand))
        <div class="product-card__brand product-card-brand">{{ $product->brand->name }}</div>
        @endif

        <a href="{{ $product->url }}" class="product-card__title product-card-title">{{ $product->name }}</a>

        <div class="product-card__price product-card-price">
            <strong>{{ number_format($product->price, 0, '.', ' ') }} ₸</strong>
            @if(!empty($product->old_price) && $product->old_price > $product->price)
            <del>{{ number_format($product->old_price, 0, '.', ' ') }} ₸</del>
            @endif
        </div>

        <div class="product-card__actions">
            @if(!empty($product->sku))
            <a href="{{ $product->url }}" class="product-card__kaspi product-card-button">Kaspi рассрочка</a>
            @else
            <a href="{{ $product->url }}" class="product-card__kaspi product-card-button" style="background:#111">Подробнее</a>
            @endif
            @if($wa)
            <a href="https://wa.me/{{ $wa }}?text={{ $waMsg }}" target="_blank" rel="noopener" class="product-card__wa" aria-label="Купить в WhatsApp">
                <svg width="17" height="17" fill="currentColor" viewBox="0 0 24 24"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413z"/></svg>
            </a>
            @endif
            <a href="{{ $product->url }}" class="product-card__details">Открыть товар</a>
        </div>
    </div>

</article>

<div class="product-quick" data-quick-modal="{{ $product->id }}" hidden>
    <div class="product-quick__box">
        <button type="button" class="product-quick__close" data-quick-close aria-label="Закрыть">×</button>
        <div class="product-quick__image">
            <img src="{{ $thumb }}" alt="{{ $product->name }}" loading="lazy" decoding="async" width="320" height="320">
        </div>
        <div style="padding:8px 4px 4px">
            @if(!empty($product->brand))<div class="product-card__brand">{{ $product->brand->name }}</div>@endif
            <h3 style="font-size:22px;line-height:1.25;margin:0 0 12px;color:#111">{{ $product->name }}</h3>
            <div class="product-card__price" style="margin-bottom:16px"><strong>{{ number_format($product->price, 0, '.', ' ') }} ₸</strong></div>
            @if(!empty($product->short_description))
            <p style="font-size:14px;color:#666;line-height:1.6;margin-bottom:18px">{{ \Illuminate\Support\Str::limit(strip_tags($product->short_description), 180) }}</p>
            @endif
            <div style="display:flex;gap:10px;flex-wrap:wrap">
                <a href="{{ $product->url }}" class="btn-orange-sm">Подробнее</a>
                @if($wa)<a href="https://wa.me/{{ $wa }}?text={{ $waMsg }}" target="_blank" rel="noopener" class="btn-wa-sm" style="width:auto;padding-left:16px;padding-right:16px">WhatsApp</a>@endif
            </div>
        </div>
    </div>
</div>
