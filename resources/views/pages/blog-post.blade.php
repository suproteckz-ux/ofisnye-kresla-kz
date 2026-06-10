@extends('layouts.app')

@section('title', $post->seoTitle())
@section('description', $post->seoDescription())

@section('canonical')
<link rel="canonical" href="{{ $post->url }}">
@endsection

@php
    $ogImage = $post->cover_image
        ? asset('storage/' . ($post->cover_image_webp ?? $post->cover_image))
        : asset('img/og-default.jpg');
    $ogType = 'article';
@endphp

@section('breadcrumbs')
<x-ui.breadcrumbs :items="[
    ['name' => 'Блог', 'url' => route('blog')],
    ['name' => $post->title],
]"/>
@endsection

@section('content')
<div class="container mx-auto px-4 py-8 max-w-4xl">

    {{-- Обложка --}}
    @if($post->cover_image)
    <div class="aspect-video rounded-2xl overflow-hidden mb-8 bg-gray-100">
        <img src="{{ asset('storage/' . ($post->cover_image_webp ?? $post->cover_image)) }}"
             alt="{{ $post->cover_image_alt ?? $post->title }}"
             class="w-full h-full object-cover"
             width="900" height="506"
             fetchpriority="high">
    </div>
    @endif

    <h1 class="text-2xl md:text-3xl font-bold text-gray-900 mb-4">
        {{ $post->seoH1() }}
    </h1>

    <div class="flex items-center gap-4 text-sm text-gray-400 mb-8">
        @if($post->published_at)
        <time datetime="{{ $post->published_at->format('Y-m-d') }}">
            {{ $post->published_at->translatedFormat('d F Y') }}
        </time>
        @endif
    </div>

    {{-- Контент --}}
    @if($post->content)
    <div class="prose prose-gray max-w-none">
        {!! $post->content !!}
    </div>
    @endif

    {{-- Связанные товары --}}
    @if(isset($relatedProducts) && $relatedProducts->count())
    <div class="mt-12 pt-8 border-t border-gray-100">
        <h2 class="text-xl font-bold text-gray-900 mb-6">Товары из статьи</h2>
        <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
            @foreach($relatedProducts as $product)
                <x-product.card :product="$product"/>
            @endforeach
        </div>
    </div>
    @endif

    {{-- Другие статьи --}}
    @if(isset($recent) && $recent->count())
    <div class="mt-12 pt-8 border-t border-gray-100">
        <h2 class="text-xl font-bold text-gray-900 mb-6">Читайте также</h2>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
            @foreach($recent as $rec)
            <a href="{{ $rec->url }}"
               class="flex gap-4 p-4 bg-gray-50 rounded-xl hover:bg-gray-100 transition-colors">
                @if($rec->cover_image)
                <img src="{{ asset('storage/' . ($rec->cover_image_webp ?? $rec->cover_image)) }}"
                     alt="{{ $rec->title }}"
                     class="w-20 h-16 object-cover rounded-lg flex-shrink-0"
                     loading="lazy">
                @endif
                <div>
                    <p class="font-semibold text-gray-900 text-sm leading-tight mb-1">
                        {{ $rec->title }}
                    </p>
                    @if($rec->published_at)
                    <p class="text-xs text-gray-400">
                        {{ $rec->published_at->translatedFormat('d F Y') }}
                    </p>
                    @endif
                </div>
            </a>
            @endforeach
        </div>
    </div>
    @endif

</div>
@endsection
