@php
    $ga4  = \App\Services\CacheService::setting('ga4_id', '');
    $ym   = \App\Services\CacheService::setting('yandex_metrika_id', '');
@endphp
@if($ga4 && app()->isProduction())
<script async src="https://www.googletagmanager.com/gtag/js?id={{ $ga4 }}"></script>
<script>window.dataLayer=window.dataLayer||[];function gtag(){dataLayer.push(arguments);}gtag('js',new Date());gtag('config','{{ $ga4 }}');</script>
@endif
@if($ym && app()->isProduction())
<script>(function(m,e,t,r,i,k,a){m[i]=m[i]||function(){(m[i].a=m[i].a||[]).push(arguments)};m[i].l=1*new Date();for(var j=0;j<document.scripts.length;j++){if(document.scripts[j].src===r){return;}}k=e.createElement(t),a=e.getElementsByTagName(t)[0],k.async=1,k.src=r,a.parentNode.insertBefore(k,a)})(window,document,"script","https://mc.yandex.ru/metrika/tag.js","ym");ym({{ $ym }},"init",{clickmap:true,trackLinks:true,accurateTrackBounce:true});</script>
@endif
