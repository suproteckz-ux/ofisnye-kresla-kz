@extends('layouts.app')
@section('title', 'Каталог офисных кресел в Алматы | ' . config('app.name'))
@section('description', 'Каталог офисных кресел в Алматы: кресла для руководителей, эргономичные, компьютерные, игровые. Большой выбор, доставка по Казахстану.')
@if($noindex ?? false)
@section('noindex', true)
@endif

@section('canonical')
<link rel="canonical" href="{{ $canonical ?? url('/catalog') }}">
@if(isset($products) && $products->currentPage() > 1 && $products->previousPageUrl())
    <link rel="prev" href="{{ $products->previousPageUrl() }}">
@endif
@if(isset($products) && $products->hasMorePages())
    <link rel="next" href="{{ $products->nextPageUrl() }}">
@endif
@endsection

@section('schema')
@php
$schemaBC = ['@context'=>'https://schema.org','@type'=>'BreadcrumbList','itemListElement'=>[
    ['@type'=>'ListItem','position'=>1,'name'=>'Главная','item'=>url('/')],
    ['@type'=>'ListItem','position'=>2,'name'=>'Каталог','item'=>url('/catalog')],
]];
@endphp
<script type="application/ld+json">{!! json_encode($schemaBC, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES) !!}</script>
@endsection

@section('breadcrumbs')
<x-ui.breadcrumbs :items="$breadcrumbs ?? []"/>
@endsection

@section('content')
<div class="container" style="padding-top:24px;padding-bottom:48px">

    <h1 style="font-size:clamp(1.5rem,3vw,2rem);font-weight:800;color:#111;margin-bottom:8px">
        Каталог офисных кресел в Алматы
    </h1>

    @if(isset($products))

    {{-- Плитки подкатегорий (дочерние к «Офисные кресла», сама категория не показывается) --}}
    @if(($subCategories ?? collect())->count())
    <div style="display:grid;
                grid-template-columns:repeat(2,1fr);
                gap:10px;margin:16px 0 28px"
         class="cat-tiles">
        @foreach($subCategories as $cat)
        <a href="{{ $cat->url }}"
           style="display:flex;align-items:center;justify-content:space-between;
                  padding:14px 18px;background:#fff;border:1.5px solid #eee;
                  border-radius:12px;text-decoration:none;
                  transition:border-color .2s,background .2s"
           onmouseover="this.style.borderColor='#ff8a00';this.style.background='#fffbf5'"
           onmouseout="this.style.borderColor='#eee';this.style.background='#fff'">
            <span style="font-size:14px;font-weight:600;color:#111">{{ $cat->name }}</span>
            @if(($cat->products_count ?? 0) > 0)
            <span style="font-size:12px;color:#aaa;background:#f5f5f5;padding:2px 8px;border-radius:99px;flex-shrink:0;margin-left:8px">
                {{ $cat->products_count }}
            </span>
            @endif
        </a>
        @endforeach
    </div>

    <style>
    @media(min-width:640px){.cat-tiles{grid-template-columns:repeat(3,1fr)!important}}
    @media(min-width:1024px){.cat-tiles{grid-template-columns:repeat(4,1fr)!important}}
    </style>
    @endif

    {{-- Счётчик + сортировка --}}
    <div style="display:flex;align-items:center;justify-content:space-between;
                gap:12px;margin-bottom:20px;flex-wrap:wrap">
        <p style="font-size:14px;color:#666">{{ $products->total() }} товаров</p>
        <select onchange="window.location=this.value"
                style="padding:8px 14px;border:1px solid #eee;border-radius:8px;
                       font-size:13px;background:#fff;cursor:pointer;outline:none;color:#111">
            @foreach(['default'=>'По популярности','price_asc'=>'Цена ↑','price_desc'=>'Цена ↓','new'=>'Сначала новые'] as $val=>$label)
            <option value="{{ request()->fullUrlWithQuery(['sort'=>$val,'page'=>null]) }}"
                    {{ ($sort??'default')===$val?'selected':'' }}>{{ $label }}</option>
            @endforeach
        </select>
    </div>

    {{-- Товары --}}
    @if($products->count())
    <div class="pgrid">
        @foreach($products as $product)
            @include('components.product.card', ['product'=>$product])
        @endforeach
    </div>
    <div style="margin-top:32px;display:flex;justify-content:center">
        {{ $products->links() }}
    </div>
    @else
    <div style="text-align:center;padding:60px 0">
        <p style="color:#666;font-size:15px;margin-bottom:16px">Товары не найдены</p>
        <a href="{{ url('/catalog') }}" class="btn-orange">Весь каталог</a>
    </div>
    @endif
    @endif

</div>
@endsection
