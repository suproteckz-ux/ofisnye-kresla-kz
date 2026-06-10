@extends('layouts.app')

@section('title', 'Бренды офисных кресел в Алматы | ' . config('app.name'))
@section('description', 'Все бренды офисных кресел в нашем каталоге. Купить офисное кресло известного производителя в Алматы с доставкой по Казахстану.')

@section('canonical')
<link rel="canonical" href="{{ url('/brand') }}">
@endsection

@section('content')
<div class="container mx-auto px-4 py-8">

    <h1 style="font-size:1.75rem;font-weight:700;color:#1c1917;margin-bottom:8px">
        Бренды офисных кресел
    </h1>
    <p style="color:#78716c;margin-bottom:32px">Производители кресел, представленные в нашем каталоге</p>

    @if(isset($brands) && $brands->count())
    <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(200px,1fr));gap:16px">
        @foreach($brands as $brand)
        <a href="{{ route('brand.show', $brand->slug) }}"
           style="display:flex;flex-direction:column;align-items:center;padding:24px 16px;
                  background:#fafaf9;border:1px solid #e7e5e4;border-radius:16px;text-decoration:none;
                  transition:border-color 0.2s,background 0.2s;text-align:center"
           onmouseover="this.style.borderColor='#fde68a';this.style.background='#fffbeb'"
           onmouseout="this.style.borderColor='#e7e5e4';this.style.background='#fafaf9'">

            @if($brand->logo)
            <picture style="display:block;height:56px;display:flex;align-items:center;margin-bottom:12px">
                @if($brand->logo_webp)
                <source srcset="{{ asset('storage/'.$brand->logo_webp) }}" type="image/webp">
                @endif
                <img src="{{ asset('storage/'.$brand->logo) }}"
                     alt="{{ $brand->name }}"
                     style="max-height:48px;max-width:120px;object-fit:contain"
                     loading="lazy">
            </picture>
            @else
            <div style="height:56px;display:flex;align-items:center;justify-content:center;margin-bottom:12px">
                <span style="font-size:2rem">🪑</span>
            </div>
            @endif

            <span style="font-weight:600;color:#1c1917;font-size:14px;margin-bottom:4px">
                {{ $brand->name }}
            </span>
            @if(isset($brand->products_count) && $brand->products_count > 0)
            <span style="font-size:12px;color:#a8a29e">{{ $brand->products_count }} кресел</span>
            @endif
        </a>
        @endforeach
    </div>
    @else
    <div style="padding:64px;text-align:center;background:#fafaf9;border-radius:16px">
        <p style="color:#a8a29e">Бренды появятся после импорта товаров</p>
    </div>
    @endif
</div>
@endsection
