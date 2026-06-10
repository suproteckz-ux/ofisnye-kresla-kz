@php $whatsapp = \App\Services\CacheService::setting('whatsapp', ''); @endphp
@if($whatsapp)
<a href="https://wa.me/{{ $whatsapp }}" target="_blank" rel="noopener"
   style="position:fixed;bottom:24px;right:24px;z-index:999;
          display:flex;align-items:center;justify-content:center;
          width:56px;height:56px;background:#22c55e;border-radius:50%;
          box-shadow:0 4px 16px rgba(34,197,94,0.4);
          transition:transform .2s,background .2s;text-decoration:none"
   onmouseover="this.style.transform='scale(1.1)';this.style.background='#16a34a'"
   onmouseout="this.style.transform='scale(1)';this.style.background='#22c55e'"
   aria-label="Написать в WhatsApp">
    <svg width="28" height="28" fill="#fff" viewBox="0 0 24 24">
        <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413z"/>
        <path fill-rule="evenodd" clip-rule="evenodd" d="M12 2C6.477 2 2 6.477 2 12c0 1.9.524 3.675 1.438 5.193L2 22l4.807-1.438A9.956 9.956 0 0012 22c5.523 0 10-4.477 10-10S17.523 2 12 2zM4 12a8 8 0 1114.93 4.065l.07.13-1.016 3.043-3.043-1.016-.13-.07A8 8 0 014 12z"/>
    </svg>
</a>
@endif
