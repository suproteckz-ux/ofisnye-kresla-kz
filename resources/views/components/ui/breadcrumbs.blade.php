@props(['items' => []])
@if(count($items))
<nav class="site-breadcrumbs" aria-label="Хлебные крошки">
    <ol>
        <li><a href="{{ route('home') }}">Главная</a></li>
        @foreach($items as $item)
        <li>
            <span class="bc-divider">/</span>
            @if(isset($item['url']))
                <a href="{{ $item['url'] }}">{{ $item['name'] }}</a>
            @else
                <span class="bc-current">{{ $item['name'] }}</span>
            @endif
        </li>
        @endforeach
    </ol>
</nav>
<style>
.site-breadcrumbs ol{display:flex;align-items:center;gap:6px;flex-wrap:wrap;list-style:none;margin:0;padding:0;font-size:13px;color:#888}
.site-breadcrumbs li{display:flex;align-items:center;gap:6px}.site-breadcrumbs a{color:#888}.site-breadcrumbs a:hover{color:#d97706}
.site-breadcrumbs .bc-divider{color:#d6d3d1}.site-breadcrumbs .bc-current{color:#57534e;font-weight:500}
</style>
@endif
