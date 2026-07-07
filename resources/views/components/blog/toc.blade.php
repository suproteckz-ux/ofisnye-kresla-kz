@props(['items' => []])

@if(count($items))
<nav class="blog-toc" aria-label="Содержание статьи">
    <div class="blog-side-title">Содержание</div>
    <ol>
        @foreach($items as $item)
        <li class="{{ ($item['level'] ?? 2) === 3 ? 'is-child' : '' }}">
            <a href="#{{ $item['id'] }}" data-blog-toc-link>{{ $item['title'] }}</a>
        </li>
        @endforeach
    </ol>
</nav>
@endif
