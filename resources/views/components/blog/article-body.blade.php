@props(['html' => ''])

<article class="blog-article-body">
    @if(trim(strip_tags((string) $html)) !== '')
        {!! $html !!}
    @else
        <p>Материал скоро будет обновлен.</p>
    @endif
</article>
