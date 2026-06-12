@extends('layouts.app')

@section('title', 'Акции на офисные кресла в Алматы | ' . config('app.name'))
@section('description', 'Актуальные предложения и популярные модели офисных кресел в Алматы. Выбирайте хиты продаж с доставкой по Казахстану.')

@section('canonical')
<link rel="canonical" href="{{ route('promotions') }}">
@endsection

@section('breadcrumbs')
<x-ui.breadcrumbs :items="[['name' => 'Акции']]"/>
@endsection

@section('content')
<section class="info-products-page">
    <div class="container">
        <header class="info-page-intro">
            <h1>Акции</h1>
            <p>Актуальные предложения и популярные модели офисных кресел</p>
        </header>

        @if($products->isNotEmpty())
        <div class="pgrid">
            @foreach($products as $product)
                @include('components.product.card', ['product' => $product])
            @endforeach
        </div>
        @else
        <div class="info-empty">Актуальные предложения скоро появятся.</div>
        @endif
    </div>
</section>

<style>
.info-products-page{padding:28px 0 56px}
.info-page-intro{margin-bottom:26px}.info-page-intro h1{font-size:clamp(1.75rem,4vw,2.3rem);font-weight:800;margin-bottom:8px}
.info-page-intro p{font-size:15px;color:#666}.info-empty{padding:48px 20px;text-align:center;background:#fafaf9;border-radius:16px;color:#777}
</style>
@endsection

@section('schema')
<x-schema.breadcrumbs :items="[['name' => 'Акции', 'url' => route('promotions')]]"/>
@endsection
