@php($url = url()->current())

<section class="blog-share">
    <div class="blog-side-title">Поделиться</div>
    <div class="blog-share__grid">
        <a href="https://wa.me/?text={{ urlencode($url) }}" target="_blank" rel="noopener">WhatsApp</a>
        <a href="https://t.me/share/url?url={{ urlencode($url) }}" target="_blank" rel="noopener">Telegram</a>
        <a href="https://www.facebook.com/sharer/sharer.php?u={{ urlencode($url) }}" target="_blank" rel="noopener">Facebook</a>
        <button type="button" data-blog-copy-url="{{ $url }}">Ссылка</button>
    </div>
</section>
