@props(['items' => []])
@if(count($items))
<nav aria-label="Хлебные крошки">
    <ol class="flex items-center gap-1 text-sm text-gray-500 flex-wrap">
        <li><a href="{{ route('home') }}" class="hover:text-amber-500">Главная</a></li>
        @foreach($items as $item)
        <li class="flex items-center gap-1">
            <span class="text-gray-300">/</span>
            @if(isset($item['url']))
                <a href="{{ $item['url'] }}" class="hover:text-amber-500">{{ $item['name'] }}</a>
            @else
                <span class="text-gray-700 font-medium">{{ $item['name'] }}</span>
            @endif
        </li>
        @endforeach
    </ol>
</nav>
@endif
