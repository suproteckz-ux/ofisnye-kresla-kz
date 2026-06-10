@extends('layouts.app')

@section('title', $brand->seoTitle())
@section('description', $brand->seoDescription())

@section('canonical')
<link rel="canonical" href="{{ $canonical }}">
@if($noindex)
<meta name="robots" content="noindex, nofollow">
@endif
@endsection

@section('breadcrumbs')
<x-ui.breadcrumbs :items="[
    ['name' => 'Бренды', 'url' => route('brands')],
    ['name' => $brand->name],
]"/>
@endsection

@section('content')
<div class="container mx-auto px-4 py-8">

    {{-- Шапка бренда --}}
    <div class="flex items-center gap-6 mb-8">
        @if($brand->logo)
        <img src="{{ asset('storage/' . ($brand->logo_webp ?? $brand->logo)) }}"
             alt="{{ $brand->name }}"
             class="h-20 object-contain"
             width="160" height="80">
        @endif
        <div>
            <h1 class="text-2xl md:text-3xl font-bold text-gray-900">
                {{ $brand->seoH1() }}
            </h1>
            @if($brand->description)
            <p class="text-gray-500 text-sm mt-2 max-w-2xl">{{ $brand->description }}</p>
            @endif
        </div>
    </div>

    {{-- Сортировка --}}
    <div class="flex items-center justify-between mb-6 flex-wrap gap-4">
        <p class="text-sm text-gray-500">
            Найдено:
            <span class="font-semibold text-gray-900">{{ $products->total() }}</span>
            товаров бренда {{ $brand->name }}
        </p>
        <select onchange="window.location = this.value"
                class="text-sm border border-gray-200 rounded-lg px-3 py-2">
            @foreach([
                'default'    => 'По умолчанию',
                'price_asc'  => 'Цена: по возрастанию',
                'price_desc' => 'Цена: по убыванию',
                'new'        => 'Сначала новые',
            ] as $val => $label)
            <option value="{{ request()->fullUrlWithQuery(['sort' => $val, 'page' => null]) }}"
                    {{ ($sort ?? 'default') === $val ? 'selected' : '' }}>
                {{ $label }}
            </option>
            @endforeach
        </select>
    </div>

    {{-- Товары --}}
    @if($products->count())
    <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
        @foreach($products as $product)
            <x-product.card :product="$product"/>
        @endforeach
    </div>

    @if($products->hasPages())
    <div class="mt-8">{{ $products->links() }}</div>
    @endif

    @else
    <div class="text-center py-16 text-gray-400">
        <div class="text-5xl mb-4">🔍</div>
        <p class="text-lg font-medium text-gray-600">Товары бренда не найдены</p>
    </div>
    @endif
</div>
@endsection
