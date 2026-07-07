@extends('layouts.app')

@section('title', $metaTitle)
@section('description', $metaDesc)
@if($noindex ?? false)
@section('noindex', true)
@endif

@push('head')
<link rel="stylesheet" href="{{ asset('css/blog.css') }}">
@endpush

@section('canonical')
<link rel="canonical" href="{{ $canonical }}">
@if(($currentPage ?? 1) > 1 && $posts->previousPageUrl())
<link rel="prev" href="{{ $posts->previousPageUrl() }}">
@endif
@if($posts->hasMorePages())
<link rel="next" href="{{ $posts->nextPageUrl() }}">
@endif
@endsection

@section('breadcrumbs')
<x-ui.breadcrumbs :items="[['name' => 'Блог']]"/>
@endsection

@section('content')
<section class="blog-shell">
    <div class="container">
        <header class="blog-list-hero">
            <div>
                <span class="blog-pill">Советы и обзоры</span>
                <h1>Статьи об офисных креслах</h1>
                <p>Разбираем эргономику, материалы, посадку и выбор кресел для дома, офиса, руководителей и сотрудников.</p>
            </div>
            <div class="blog-list-hero__art" aria-hidden="true"></div>
        </header>

        @if($featuredPost)
            <x-blog.featured-article :post="$featuredPost" />
        @endif

        @if($posts->count())
        <div class="blog-grid">
            @foreach($posts as $post)
                @if(!$featuredPost || $post->id !== $featuredPost->id)
                    <x-blog.article-card :post="$post" />
                @endif
            @endforeach
        </div>

        @if($posts->hasPages())
        <nav class="blog-pagination" aria-label="Пагинация статей">
            @if($posts->onFirstPage())
            <span>Назад</span>
            @else
            <a href="{{ $posts->previousPageUrl() }}" rel="prev">Назад</a>
            @endif

            <span>Страница {{ $posts->currentPage() }} из {{ $posts->lastPage() }}</span>

            @if($posts->hasMorePages())
            <a href="{{ $posts->nextPageUrl() }}" rel="next">Вперёд</a>
            @else
            <span>Вперёд</span>
            @endif
        </nav>
        @endif
        @else
        <div class="blog-empty">
            <h2>Статей не найдено</h2>
            <p>Материалы блога скоро появятся.</p>
        </div>
        @endif
    </div>
</section>
@endsection

@section('schema')
<x-schema.breadcrumbs :items="[
    ['name' => 'Блог', 'url' => url('/blog')],
]"/>
@endsection
