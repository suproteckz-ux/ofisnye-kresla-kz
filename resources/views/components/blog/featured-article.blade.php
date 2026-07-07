@props(['post'])

@if($post)
@php
    $excerpt = \App\Support\BlogContent::excerpt($post, 190);
    $topic = \App\Support\BlogContent::topic($post);
@endphp
<article class="blog-featured">
    @if($post->cover_image)
    <a class="blog-featured__image" href="{{ $post->url }}">
        <x-blog.image :image="$post->cover_image" :webp="$post->cover_image_webp" :alt="$post->cover_image_alt ?? $post->title" loading="eager" fetchpriority="high" :width="720" :height="420" sizes="(max-width: 1024px) 100vw, 55vw" />
    </a>
    @endif
    <div class="blog-featured__body">
        <span class="blog-pill">{{ $topic }}</span>
        <h2><a href="{{ $post->url }}">{{ $post->title }}</a></h2>
        <p>{{ $excerpt }}</p>
        <a class="btn-orange" href="{{ $post->url }}">Читать статью</a>
    </div>
</article>
@endif
