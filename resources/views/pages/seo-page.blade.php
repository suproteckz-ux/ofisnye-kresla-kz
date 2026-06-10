@extends('layouts.app')

@section('title', $page->seoTitle())
@section('description', $page->seoDescription())

@section('canonical')
<link rel="canonical" href="{{ $page->url }}">
@endsection

@section('breadcrumbs')
<x-ui.breadcrumbs :items="$breadcrumbs ?? []"/>
@endsection

@section('content')
<div class="container mx-auto px-4 py-8">

    <h1 class="text-2xl md:text-3xl font-bold text-gray-900 mb-6">
        {{ $page->seoH1() }}
    </h1>

    @if($page->seo_text)
    <div class="prose prose-gray max-w-none text-gray-600 mb-8">
        {!! $page->seo_text !!}
    </div>
    @endif

    {{-- Связанные товары --}}
    @if($page->products && $page->products->count())
    <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4 mb-8">
        @foreach($page->products as $product)
            <x-product.card :product="$product"/>
        @endforeach
    </div>
    @endif

</div>
@endsection

@section('schema')
<x-schema.breadcrumbs :items="$breadcrumbs ?? []"/>
@endsection
