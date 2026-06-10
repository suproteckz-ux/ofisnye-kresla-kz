@php
// Безопасная защита: если карточке передали массив из кэша/контроллера,
// пробуем восстановить полноценную Eloquent-модель Product.
// Это важно: у массива/обычного object нет методов hasDiscount(), brand и других связей.
if (is_array($product)) {
    $product = \App\Models\Product::query()
        ->with(['brand'])
        ->when(!empty($product['id'] ?? null), fn($q) => $q->where('id', $product['id']))
        ->when(empty($product['id'] ?? null) && !empty($product['slug'] ?? null), fn($q) => $q->where('slug', $product['slug']))
        ->first();
}

// Если товар не найден — не роняем страницу 500, просто не выводим эту карточку.
if (!$product) {
    return;
}

$hasDiscount = method_exists($product, 'hasDiscount')
    ? $product->hasDiscount()
    : (!empty($product->old_price) && $product->old_price > $product->price);

$wa    = \App\Services\CacheService::setting('whatsapp', '');
$waMsg = urlencode('Хочу заказать: ' . ($product->name ?? '') . ' — ' . config('app.name'));
@endphp

<div style="background:#fff;border-radius:16px;border:1px solid #e7e5e4;overflow:hidden;
            display:flex;flex-direction:column;height:100%;
            transition:border-color 0.2s,box-shadow 0.2s"
     onmouseover="this.style.borderColor='#fde68a';this.style.boxShadow='0 8px 24px rgba(0,0,0,0.08)'"
     onmouseout="this.style.borderColor='#e7e5e4';this.style.boxShadow='none'">

    <a href="{{ $product->url }}"
       style="display:block;position:relative;background:#f8f8f8;overflow:hidden;flex-shrink:0;height:200px" class="card-img-wrap">

        @if(!empty($product->main_image))
        <picture>
            @if(!empty($product->main_image_webp))
            <source srcset="{{ asset('storage/'.$product->main_image_webp) }}" type="image/webp">
            @endif
            <img src="{{ asset('storage/'.$product->main_image) }}"
                 alt="{{ $product->main_image_alt ?? $product->name }}"
                 loading="lazy"
                 onerror="this.parentElement.style.display='none';this.parentElement.parentElement.querySelector('.img-placeholder').style.display='flex'"
                 style="width:100%;height:100%;object-fit:contain;padding:12px;position:absolute;top:0;left:0">
        </picture>
        @endif
        <div class="img-placeholder" style="{{ !empty($product->main_image) ? 'display:none' : 'display:flex' }};width:100%;height:100%;align-items:center;justify-content:center;background:#f5f5f4">
            <svg style="width:56px;height:56px;color:#d6d3d1" fill="currentColor" viewBox="0 0 100 100">
                <path d="M50 15C35 15 25 25 25 40v20c0 5 3 9 7 10l-2 10h10l2-10h16l2 10h10l-2-10c4-1 7-5 7-10V40c0-15-10-25-25-25z" opacity="0.5"/>
            </svg>
        </div>

        {{-- Бейджи --}}
        @if(!empty($product->is_new) || !empty($product->is_hit) || $hasDiscount)
        <div style="position:absolute;top:8px;left:8px;display:flex;flex-direction:column;gap:4px">
            @if(!empty($product->is_new))
            <span style="font-size:10px;font-weight:700;padding:2px 8px;background:#3b82f6;color:#fff;border-radius:99px">Новинка</span>
            @endif
            @if(!empty($product->is_hit))
            <span style="font-size:10px;font-weight:700;padding:2px 8px;background:#f59e0b;color:#fff;border-radius:99px">Хит</span>
            @endif
            @if($hasDiscount)
            <span style="font-size:10px;font-weight:700;padding:2px 8px;background:#ef4444;color:#fff;border-radius:99px">−{{ $product->discount_percent }}%</span>
            @endif
        </div>
        @endif

        <div style="position:absolute;bottom:8px;right:8px">
            @if($product->in_stock ?? true)
            <span style="font-size:10px;font-weight:500;padding:2px 8px;background:#dcfce7;color:#16a34a;border-radius:99px">В наличии</span>
            @else
            <span style="font-size:10px;padding:2px 8px;background:#f5f5f4;color:#a8a29e;border-radius:99px">Нет в наличии</span>
            @endif
        </div>
    </a>

    <div style="padding:14px;display:flex;flex-direction:column;flex:1">
        @if(!empty($product->brand))
        <p style="font-size:11px;color:#d97706;font-weight:600;margin-bottom:4px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap">
            {{ $product->brand->name }}
        </p>
        @endif

        <a href="{{ $product->url }}"
           style="font-size:13px;font-weight:600;color:#1c1917;line-height:1.4;margin-bottom:10px;flex:1;
                  display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden;text-decoration:none">
            {{ $product->name }}
        </a>

        <div style="display:flex;align-items:baseline;gap:8px;margin-bottom:12px">
            <span style="font-size:17px;font-weight:800;color:#1c1917">
                {{ number_format($product->price, 0, '.', ' ') }} ₸
            </span>
            @if(!empty($product->old_price) && $product->old_price > $product->price)
            <span style="font-size:12px;color:#a8a29e;text-decoration:line-through">
                {{ number_format($product->old_price, 0, '.', ' ') }} ₸
            </span>
            @endif
        </div>

        @if($wa)
        <a href="https://wa.me/{{ $wa }}?text={{ $waMsg }}" target="_blank" rel="noopener"
           style="display:block;width:100%;padding:9px;background:#22c55e;color:#fff;font-size:12px;font-weight:700;
                  text-align:center;border-radius:10px;text-decoration:none;transition:background 0.2s"
           onmouseover="this.style.background='#16a34a'" onmouseout="this.style.background='#22c55e'">
            Купить в WhatsApp
        </a>
        @else
        <a href="{{ $product->url }}"
           style="display:block;width:100%;padding:9px;background:#1c1917;color:#fff;font-size:12px;font-weight:700;
                  text-align:center;border-radius:10px;text-decoration:none">
            Подробнее →
        </a>
        @endif
    </div>
</div>
