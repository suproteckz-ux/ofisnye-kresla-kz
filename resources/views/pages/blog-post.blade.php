@extends('layouts.app')

@section('title', $post->seoTitle())
@section('description', $post->seoDescription())

@push('head')
<link rel="stylesheet" href="{{ asset('css/blog.css') }}">
@endpush

@section('canonical')
<link rel="canonical" href="{{ $post->url }}">
@endsection

@section('breadcrumbs')
<x-ui.breadcrumbs :items="[
    ['name' => 'Блог', 'url' => route('blog')],
    ['name' => $post->title],
]"/>
@endsection

@section('content')
<div class="blog-shell">
    <div class="container">
        <x-blog.hero :post="$post" :excerpt="$excerpt" :reading-time="$readingTime" />

        <main class="blog-article-page">
            <x-blog.article-body :html="$articleHtml" />

            <x-blog.cta />
            <x-blog.author />
            <x-blog.related-products :products="$relatedProducts" />
            <x-blog.related-posts :posts="$relatedPosts" />
            <x-blog.faq :items="$faq" />
        </main>
    </div>
</div>
@endsection

@section('schema')
<x-schema.breadcrumbs :items="[
    ['name' => 'Блог', 'url' => route('blog')],
    ['name' => $post->title, 'url' => $post->url],
]"/>
@php
    $articleSchema = [
        '@context' => 'https://schema.org',
        '@type' => 'Article',
        'headline' => $post->seoH1(),
        'description' => $post->seoDescription(),
        'datePublished' => optional($post->published_at)->toAtomString(),
        'dateModified' => optional($post->updated_at)->toAtomString(),
        'author' => [
            '@type' => 'Organization',
            'name' => 'NetBazar',
        ],
        'publisher' => [
            '@type' => 'Organization',
            'name' => config('app.name'),
        ],
        'mainEntityOfPage' => [
            '@type' => 'WebPage',
            '@id' => $post->url,
        ],
    ];

    if ($post->cover_image) {
        $articleSchema['image'] = [asset('storage/' . $post->cover_image)];
    }

    $faqSchema = null;
    if (!empty($faq)) {
        $faqSchema = [
            '@context' => 'https://schema.org',
            '@type' => 'FAQPage',
            'mainEntity' => array_map(fn ($item) => [
                '@type' => 'Question',
                'name' => $item['question'],
                'acceptedAnswer' => [
                    '@type' => 'Answer',
                    'text' => strip_tags((string) $item['answer']),
                ],
            ], $faq),
        ];
    }
@endphp
<script type="application/ld+json">{!! json_encode($articleSchema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!}</script>
@if($faqSchema)
<script type="application/ld+json">{!! json_encode($faqSchema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!}</script>
@endif
@endsection
