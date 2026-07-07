@props(['post', 'excerpt' => '', 'readingTime' => null])

<section class="blog-hero">
    <div class="blog-hero__content">
        <div class="blog-eyebrow">
            @if($post->published_at)
            <time datetime="{{ $post->published_at->format('Y-m-d') }}">{{ $post->published_at->translatedFormat('d F Y') }}</time>
            @endif
            <span>{{ $readingTime ?? 1 }} мин чтения</span>
        </div>
        <h1>{{ $post->seoH1() }}</h1>
        @if($excerpt)<p>{{ $excerpt }}</p>@endif
        <div class="blog-meta">
            <span>Эксперты NetBazar</span>
        </div>
    </div>

    @if($post->cover_image)
    <div class="blog-hero__media">
        <x-blog.image :image="$post->cover_image" :webp="$post->cover_image_webp" :alt="$post->cover_image_alt ?? $post->title" loading="eager" fetchpriority="high" :width="720" :height="450" sizes="(max-width: 768px) 100vw, 50vw" />
    </div>
    @endif
</section>
