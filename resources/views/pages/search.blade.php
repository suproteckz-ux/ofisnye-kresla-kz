@extends('layouts.app')

@section('title', $query
    ? "Поиск: «{$query}» | " . config('app.name')
    : 'Поиск по каталогу | ' . config('app.name')
)
{{-- Поисковые страницы не индексируются --}}
@section('canonical')<meta name="robots" content="noindex, nofollow">@endsection

@section('breadcrumbs')
<a href="{{ route('home') }}">Главная</a>
<span class="bc-sep">/</span>
<span>Поиск</span>
@if($query)
<span class="bc-sep">/</span>
<span>«{{ Str::limit($query, 40) }}»</span>
@endif
@endsection

@section('content')
<div class="container" style="padding-top:24px;padding-bottom:48px">

    <h1 style="font-size:clamp(1.4rem,3vw,1.8rem);font-weight:800;color:#111;margin-bottom:20px">
        @if($query)
            Результаты поиска: «{{ $query }}»
        @else
            Поиск по каталогу
        @endif
    </h1>

    @if($query && mb_strlen($query) < 2)

    <p style="color:#666;font-size:15px">Введите не менее 2 символов для поиска.</p>

    @elseif($query && $products instanceof \Illuminate\Contracts\Pagination\Paginator && $products->total() === 0)

    <div style="text-align:center;padding:64px 0">
        <div style="font-size:48px;margin-bottom:12px">🔍</div>
        <p style="font-size:17px;font-weight:600;color:#111;margin-bottom:8px">
            По запросу «{{ $query }}» ничего не найдено
        </p>
        <p style="font-size:14px;color:#666;margin-bottom:24px">
            Попробуйте другое название или артикул
        </p>
        <a href="{{ route('catalog') }}" class="btn-orange">Перейти в каталог</a>
    </div>

    @elseif($query && $products->count())

    {{-- Счётчик --}}
    <p style="font-size:14px;color:#666;margin-bottom:20px">
        Найдено: <strong style="color:#111">{{ $products->total() }}</strong> товаров
    </p>

    {{-- Та же сетка 2/4 что в каталоге --}}
    <div class="pgrid">
        @foreach($products as $product)
            @include('components.product.card', ['product' => $product])
        @endforeach
    </div>

    @if($products->hasPages())
    <div style="margin-top:32px;display:flex;justify-content:center">
        {{ $products->links() }}
    </div>
    @endif

    @endif

</div>
@endsection
