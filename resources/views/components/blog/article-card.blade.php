@props(['post'])

@php
    $excerpt = \App\Support\BlogContent::excerpt($post, 130);
    $topic = \App\Support\BlogContent::topic($post);
    $minutes = \App\Support\BlogContent::readingTime($post->content);
@endphp

<article class="blog-article-card">
    <a class="blog-article-card__image" href="{{ $post->url }}">
        @if($post->cover_image)
        <x-blog.image :image="$post->cover_image" :webp="$post->cover_image_webp" :alt="$post->cover_image_alt ?? $post->title" :width="480" :height="300" sizes="(max-width: 700px) 100vw, (max-width: 1024px) 50vw, 33vw" />
        @endif
    </a>
    <div class="blog-article-card__body">
        <div class="blog-card-meta">
            <span>{{ $topic }}</span>
            @if($post->published_at)<time datetime="{{ $post->published_at->format('Y-m-d') }}">{{ $post->published_at->translatedFormat('d F Y') }}</time>@endif
            <span>{{ $minutes }} мин</span>
        </div>
        <h2><a href="{{ $post->url }}">{{ $post->title }}</a></h2>
        <p>{{ $excerpt }}</p>
        <a class="blog-read-link" href="{{ $post->url }}">Читать статью</a>
    </div>
</article>
