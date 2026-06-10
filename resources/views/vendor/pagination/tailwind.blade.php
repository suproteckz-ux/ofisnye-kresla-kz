@if ($paginator->hasPages())
<nav style="display:flex;align-items:center;justify-content:center;gap:4px;flex-wrap:wrap">
    {{-- Предыдущая --}}
    @if ($paginator->onFirstPage())
    <span style="display:flex;align-items:center;justify-content:center;width:36px;height:36px;
                 border:1px solid #eee;border-radius:8px;font-size:16px;color:#ccc;cursor:not-allowed">
        ←
    </span>
    @else
    <a href="{{ $paginator->previousPageUrl() }}"
       style="display:flex;align-items:center;justify-content:center;width:36px;height:36px;
              border:1px solid #eee;border-radius:8px;font-size:16px;color:#111;text-decoration:none;
              transition:border-color .2s,color .2s"
       onmouseover="this.style.borderColor='#ff8a00';this.style.color='#ff8a00'"
       onmouseout="this.style.borderColor='#eee';this.style.color='#111'">
        ←
    </a>
    @endif

    {{-- Номера страниц --}}
    @foreach ($elements as $element)
        @if (is_string($element))
        <span style="display:flex;align-items:center;justify-content:center;width:36px;height:36px;
                     font-size:14px;color:#aaa">…</span>
        @endif
        @if (is_array($element))
            @foreach ($element as $page => $url)
                @if ($page == $paginator->currentPage())
                <span style="display:flex;align-items:center;justify-content:center;width:36px;height:36px;
                             background:#111;color:#fff;border-radius:8px;font-size:14px;font-weight:600">
                    {{ $page }}
                </span>
                @else
                <a href="{{ $url }}"
                   style="display:flex;align-items:center;justify-content:center;width:36px;height:36px;
                          border:1px solid #eee;border-radius:8px;font-size:14px;color:#111;
                          text-decoration:none;transition:border-color .2s,color .2s"
                   onmouseover="this.style.borderColor='#ff8a00';this.style.color='#ff8a00'"
                   onmouseout="this.style.borderColor='#eee';this.style.color='#111'">
                    {{ $page }}
                </a>
                @endif
            @endforeach
        @endif
    @endforeach

    {{-- Следующая --}}
    @if ($paginator->hasMorePages())
    <a href="{{ $paginator->nextPageUrl() }}"
       style="display:flex;align-items:center;justify-content:center;width:36px;height:36px;
              border:1px solid #eee;border-radius:8px;font-size:16px;color:#111;text-decoration:none;
              transition:border-color .2s,color .2s"
       onmouseover="this.style.borderColor='#ff8a00';this.style.color='#ff8a00'"
       onmouseout="this.style.borderColor='#eee';this.style.color='#111'">
        →
    </a>
    @else
    <span style="display:flex;align-items:center;justify-content:center;width:36px;height:36px;
                 border:1px solid #eee;border-radius:8px;font-size:16px;color:#ccc;cursor:not-allowed">
        →
    </span>
    @endif
</nav>
@endif
