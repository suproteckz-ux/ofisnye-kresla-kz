@props(['label', 'active' => false, 'href' => '#'])

<a class="blog-topic-pill {{ $active ? 'is-active' : '' }}" href="{{ $href }}">{{ $label }}</a>
