@extends('layouts.app')

@section('title', 'Полезное — гид по офисным креслам | ' . config('app.name'))
@section('description', 'Полезные материалы по выбору офисных кресел для дома, офиса, руководителей, сотрудников и юридических лиц.')
@section('canonical')<link rel="canonical" href="{{ $canonical }}">@endsection

@section('breadcrumbs')
<a href="{{ route('home') }}">Главная</a>
<span class="bc-sep">/</span>
<span>Полезное</span>
@endsection

@section('content')
<style>
.useful-page{padding:28px 0 54px}
.useful-hero{margin-bottom:28px}
.useful-eyebrow{color:#ff8a00;font-size:13px;font-weight:700;text-transform:uppercase;letter-spacing:.04em;margin-bottom:8px}
.useful-title{font-size:clamp(1.75rem,4vw,2.6rem);line-height:1.15;font-weight:800;color:#111;margin:0 0 12px}
.useful-lead{max-width:760px;color:#666;font-size:16px;line-height:1.7;margin:0}
.useful-grid{display:grid;grid-template-columns:1fr;gap:18px}
.useful-card{background:#fff;border:1px solid #eee;border-radius:16px;overflow:hidden;display:flex;flex-direction:column;height:100%;transition:box-shadow .2s,border-color .2s,transform .2s}
.useful-card:hover{border-color:#ffd8a3;box-shadow:0 14px 34px rgba(17,17,17,.08);transform:translateY(-2px)}
.useful-card-media{height:172px;background:#f8f8f8;display:flex;align-items:center;justify-content:center;overflow:hidden}
.useful-card-media img{width:100%;height:100%;object-fit:cover;display:block}
.useful-card-fallback{width:100%;height:100%;background:linear-gradient(135deg,#fff7ed,#f5f5f4);display:flex;align-items:center;justify-content:center;color:#ff8a00}
.useful-card-body{padding:18px;display:flex;flex-direction:column;flex:1}
.useful-card-title{font-size:18px;font-weight:800;line-height:1.28;color:#111;margin:0 0 10px;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden}
.useful-card-text{color:#666;line-height:1.55;font-size:14px;margin:0 0 18px;display:-webkit-box;-webkit-line-clamp:3;-webkit-box-orient:vertical;overflow:hidden}
.useful-card-link{margin-top:auto;display:inline-flex;align-items:center;justify-content:center;min-height:42px;padding:10px 16px;border-radius:10px;background:#ff8a00;color:#fff;font-weight:800;font-size:14px;text-decoration:none}
.useful-card-link:hover{background:#f97316;color:#fff}
@media(min-width:720px){.useful-grid{grid-template-columns:repeat(2,minmax(0,1fr))}}
@media(min-width:1024px){.useful-grid{grid-template-columns:repeat(3,minmax(0,1fr));gap:22px}.useful-page{padding-top:36px}}
</style>

<div class="container useful-page">
    <section class="useful-hero">
        <div class="useful-eyebrow">Гид по креслам</div>
        <h1 class="useful-title">Полезное</h1>
        <p class="useful-lead">Собрали практичные подборки и советы по выбору офисных кресел для разных задач: рабочие места, руководители, юридические лица, домашний офис и эргономика.</p>
    </section>

    <section class="useful-grid" aria-label="Полезные материалы">
        @foreach($pages as $page)
        @php
            $title = $page->seoH1();
            $description = $page->hero_subtitle ?: $page->meta_description ?: 'Короткий гид по выбору офисного кресла под задачу, бюджет и формат работы.';
        @endphp
        <article class="useful-card">
            <a class="useful-card-media" href="{{ $page->url }}" aria-label="{{ $title }}">
                @if($page->hero_image)
                <img src="{{ asset('storage/' . ltrim($page->hero_image, '/')) }}" alt="{{ $title }}" loading="lazy">
                @else
                <span class="useful-card-fallback" aria-hidden="true">
                    <svg width="72" height="72" fill="none" viewBox="0 0 72 72">
                        <rect x="10" y="12" width="52" height="42" rx="12" stroke="currentColor" stroke-width="3"/>
                        <path d="M24 42h24M24 30h18" stroke="currentColor" stroke-width="3" stroke-linecap="round"/>
                    </svg>
                </span>
                @endif
            </a>
            <div class="useful-card-body">
                <h2 class="useful-card-title">{{ $title }}</h2>
                <p class="useful-card-text">{{ $description }}</p>
                <a href="{{ $page->url }}" class="useful-card-link">Читать</a>
            </div>
        </article>
        @endforeach
    </section>
</div>
@endsection
