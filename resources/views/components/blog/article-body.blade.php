@props(['html' => ''])

@php
    $parts = preg_split('/(<\/p>)/i', (string) $html, -1, PREG_SPLIT_DELIM_CAPTURE);
    $paragraphs = [];

    for ($i = 0; $i < count($parts); $i += 2) {
        $chunk = ($parts[$i] ?? '') . ($parts[$i + 1] ?? '');
        if (trim(strip_tags($chunk)) !== '') {
            $paragraphs[] = $chunk;
        } elseif (trim($chunk) !== '') {
            $paragraphs[] = $chunk;
        }
    }

    $firstCtaAfter = max(1, (int) floor(count($paragraphs) * .3));
    $secondCtaAfter = max($firstCtaAfter + 1, (int) floor(count($paragraphs) * .7));
@endphp

<article class="blog-article-body" data-blog-article>
    @forelse($paragraphs as $index => $paragraph)
        {!! $paragraph !!}
        @if($index + 1 === $firstCtaAfter)
            <x-blog.cta />
        @endif
        @if($index + 1 === $secondCtaAfter && count($paragraphs) > 4)
            <x-blog.cta variant="catalog" />
        @endif
    @empty
        <p>Материал скоро будет обновлен.</p>
    @endforelse
</article>
