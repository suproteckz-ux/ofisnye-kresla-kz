@extends('layouts.app')

@section('title', $metaTitle)
@section('description', $metaDesc)

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
<section class="blog-page">
    <div class="container">
        <div class="blog-intro">
            <h1>Статьи об офисных креслах</h1>
            <p>Советы по выбору офисных кресел, обзоры моделей, брендов и эргономики рабочего места.</p>
        </div>

        @if($posts->count())
        <div class="blog-grid">
            @foreach($posts as $post)
            <article class="blog-card">
                @if($post->cover_image)
                <a href="{{ $post->url }}" class="blog-card-image">
                    <picture>
                        @if($post->cover_image_webp)
                        <source srcset="{{ asset('storage/' . $post->cover_image_webp) }}" type="image/webp">
                        @endif
                        <img src="{{ asset('storage/' . $post->cover_image) }}"
                             alt="{{ $post->cover_image_alt ?? $post->title }}"
                             loading="lazy" width="640" height="360">
                    </picture>
                </a>
                @endif

                <div class="blog-card-body">
                    @if($post->published_at)
                    <time datetime="{{ $post->published_at->format('Y-m-d') }}">
                        {{ $post->published_at->translatedFormat('d F Y') }}
                    </time>
                    @endif
                    <h2><a href="{{ $post->url }}">{{ $post->title }}</a></h2>
                    <a href="{{ $post->url }}" class="blog-read-more">
                        Читать статью <span aria-hidden="true">→</span>
                    </a>
                </div>
            </article>
            @endforeach
        </div>

        @if($posts->hasPages())
        <nav class="blog-pagination" aria-label="Пагинация статей">
            @if($posts->onFirstPage())
            <span class="is-disabled">← Назад</span>
            @else
            <a href="{{ $posts->previousPageUrl() }}" rel="prev">← Назад</a>
            @endif

            <span class="blog-page-number">
                Страница {{ $posts->currentPage() }} из {{ $posts->lastPage() }}
            </span>

            @if($posts->hasMorePages())
            <a href="{{ $posts->nextPageUrl() }}" rel="next">Вперёд →</a>
            @else
            <span class="is-disabled">Вперёд →</span>
            @endif
        </nav>
        @endif
        @else
        <div class="blog-empty">
            <h2>Статей пока нет</h2>
            <p>Скоро здесь появятся полезные материалы об офисных креслах.</p>
        </div>
        @endif
    </div>
</section>

<style>
.blog-page{padding:28px 0 56px}
.blog-intro{max-width:760px;margin-bottom:28px}
.blog-intro h1{font-size:clamp(1.75rem,4vw,2.4rem);font-weight:800;line-height:1.15;margin-bottom:10px}
.blog-intro p{font-size:15px;color:#666;line-height:1.65}
.blog-grid{display:grid;grid-template-columns:1fr;gap:18px}
.blog-card{display:flex;flex-direction:column;min-width:0;background:#fff;border:1px solid #e7e5e4;border-radius:16px;overflow:hidden;transition:border-color .2s,box-shadow .2s}
.blog-card:hover{border-color:#fed7aa;box-shadow:0 10px 30px rgba(0,0,0,.07)}
.blog-card-image{display:block;aspect-ratio:16/9;background:#f5f5f4;overflow:hidden}
.blog-card-image picture,.blog-card-image img{display:block;width:100%;height:100%}
.blog-card-image img{object-fit:cover;transition:transform .25s}
.blog-card:hover .blog-card-image img{transform:scale(1.025)}
.blog-card-body{display:flex;flex-direction:column;flex:1;padding:18px}
.blog-card time{display:block;font-size:12px;color:#a8a29e;margin-bottom:8px}
.blog-card h2{font-size:17px;line-height:1.4;font-weight:700;margin-bottom:18px}
.blog-card h2 a:hover{color:#d97706}
.blog-read-more{display:inline-flex;align-items:center;gap:6px;margin-top:auto;color:#d97706;font-size:13px;font-weight:700}
.blog-read-more span{font-size:16px;line-height:1}
.blog-pagination{display:flex;align-items:center;justify-content:center;gap:10px;flex-wrap:wrap;margin-top:32px}
.blog-pagination a,.blog-pagination>span{padding:9px 13px;border:1px solid #e7e5e4;border-radius:9px;font-size:13px;background:#fff}
.blog-pagination a:hover{border-color:#ff8a00;color:#d97706}
.blog-pagination .blog-page-number{border-color:transparent;background:#f5f5f4}
.blog-pagination .is-disabled{color:#bbb}
.blog-empty{padding:52px 20px;text-align:center;background:#fafaf9;border-radius:16px}
.blog-empty h2{font-size:20px;margin-bottom:6px}.blog-empty p{color:#777}
@media(min-width:640px){.blog-grid{grid-template-columns:repeat(2,minmax(0,1fr))}}
@media(min-width:1024px){.blog-page{padding-top:36px}.blog-grid{grid-template-columns:repeat(3,minmax(0,1fr));gap:22px}}
</style>
@endsection

@section('schema')
<x-schema.breadcrumbs :items="[
    ['name' => 'Блог', 'url' => url('/blog')],
]"/>
@endsection
