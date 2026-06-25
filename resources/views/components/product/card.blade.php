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
$waMsg = urlencode('Хочу заказать: ' . ($product->name ?? '') . ' - ' . config('app.name'));
$image = ltrim((string) ($product->main_image ?? ''), '/');
$webp = ltrim((string) ($product->main_image_webp ?? ''), '/');
$disk = \Illuminate\Support\Facades\Storage::disk('public');
$srcset = '';

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

    $srcset = implode(', ', $items);
}
@endphp

@once
<style>
.product-card{position:relative;background:#fff;border-radius:16px;border:1px solid #ececec;overflow:hidden;display:flex;flex-direction:column;height:100%;box-shadow:0 6px 18px rgba(17,17,17,.035);transition:transform .22s ease,border-color .22s ease,box-shadow .22s ease;cursor:pointer}
.product-card__media{display:block;position:relative;background:#fafafa;overflow:hidden;flex-shrink:0;height:180px!important}
.product-card__media img{position:absolute;inset:0;width:100%;height:100%;object-fit:contain;padding:14px;transition:transform .28s ease}
.product-card__badges{position:absolute;top:10px;left:10px;display:flex;flex-direction:column;gap:5px;z-index:2}
.product-card__badge{font-size:10px;font-weight:800;padding:3px 8px;border-radius:999px;color:#fff}
.product-card__badge--new{background:#14b8a6}.product-card__badge--hit{background:#8b5cf6}.product-card__badge--sale{background:#ff5a1f}
.product-card__stock{position:absolute;left:10px;bottom:10px;z-index:2;font-size:10px;font-weight:700;padding:3px 8px;background:#eafaf0;color:#16a34a;border-radius:999px}
.product-card__stock--out{background:#f4f4f5;color:#71717a}
.product-card__body{padding:16px;display:flex;flex-direction:column;flex:1;min-width:0}
.product-card__brand{font-size:11px;color:#d97706;font-weight:800;margin-bottom:5px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;text-transform:uppercase}
.product-card__title{font-size:14px;font-weight:700;color:#111;line-height:1.38;margin-bottom:10px;min-height:39px;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden;text-decoration:none}
.product-card__footer{display:flex;align-items:center;justify-content:space-between;gap:10px;margin-top:auto}
.product-card__price{display:flex;align-items:baseline;gap:8px;flex-wrap:wrap;margin:0;min-width:0}
.product-card__price strong{font-size:18px;font-weight:900;color:#111}.product-card__price del{font-size:12px;color:#aaa}
.product-card__wa{width:38px;height:38px;border-radius:50%;background:#22c55e;color:#fff;display:grid;place-items:center;text-decoration:none;flex-shrink:0}
@media(hover:hover) and (pointer:fine){
  .product-card:hover{transform:translateY(-3px);border-color:#f3d4aa;box-shadow:0 14px 34px rgba(17,17,17,.09)}
  .product-card:hover .product-card__media img{transform:scale(1.035)}
}
@media(max-width:640px){
  .product-card__media{height:150px!important}
  .product-card__body{padding:12px}
  .product-card__title{font-size:12px;min-height:34px;margin-bottom:8px}
  .product-card__price strong{font-size:15px}
  .product-card__wa{width:36px;height:36px}
}
</style>
@endonce

<article class="product-card"
         data-product-card
         data-product-id="{{ $product->id }}"
         data-product-url="{{ $product->url }}"
         onclick="if (!event.target.closest('a,button')) window.location.href=this.dataset.productUrl">
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

        <span class="product-card__stock {{ ($product->in_stock ?? true) ? '' : 'product-card__stock--out' }}">
            {{ ($product->in_stock ?? true) ? 'В наличии' : 'Нет в наличии' }}
        </span>
    </a>

    <div class="product-card__body product-card-body">
        @if(!empty($product->brand))
        <div class="product-card__brand product-card-brand">{{ $product->brand->name }}</div>
        @endif

        <a href="{{ $product->url }}" class="product-card__title product-card-title">{{ $product->name }}</a>

        <div class="product-card__footer">
            <div class="product-card__price product-card-price">
                <strong>{{ number_format($product->price, 0, '.', ' ') }} ₸</strong>
                @if(!empty($product->old_price) && $product->old_price > $product->price)
                <del>{{ number_format($product->old_price, 0, '.', ' ') }} ₸</del>
                @endif
            </div>
            @if($wa)
            <a href="https://wa.me/{{ $wa }}?text={{ $waMsg }}" target="_blank" rel="noopener" class="product-card__wa" aria-label="Купить в WhatsApp">
                <svg width="17" height="17" fill="currentColor" viewBox="0 0 24 24"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413z"/></svg>
            </a>
            @endif
        </div>
    </div>
</article>
