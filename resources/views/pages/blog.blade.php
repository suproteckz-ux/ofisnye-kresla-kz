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

        <div class="blog-tools">
            <div class="blog-topics" aria-label="Категории статей">
                @foreach($topics as $topicLabel)
                    @php
                        $isAll = $topicLabel === 'Все';
                        $href = route('blog', array_filter([
                            'q' => $search ?: null,
                            'topic' => $isAll ? null : $topicLabel,
                        ]));
                    @endphp
                    <x-blog.category-pill :label="$topicLabel" :href="$href" :active="$isAll ? ($topic === '') : ($topic === $topicLabel)" />
                @endforeach
            </div>

            <form class="blog-search" action="{{ route('blog') }}" method="GET" role="search">
                @if($topic)
                <input type="hidden" name="topic" value="{{ $topic }}">
                @endif
                <input type="search" name="q" value="{{ $search }}" placeholder="Поиск по статьям" aria-label="Поиск по статьям">
                <button type="submit">Найти</button>
            </form>
        </div>

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
            <p>Попробуйте изменить запрос или открыть все материалы блога.</p>
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
