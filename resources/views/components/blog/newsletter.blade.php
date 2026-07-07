@php($wa = \App\Services\CacheService::setting('whatsapp', ''))

<section class="blog-newsletter">
    <h2>Хотите получить подборку кресел?</h2>
    <p>Напишите нам, и мы отправим несколько вариантов под вашу задачу и бюджет.</p>
    @if($wa)
    <a class="btn-wa" href="https://wa.me/{{ $wa }}?text={{ urlencode('Здравствуйте! Хочу получить подборку офисных кресел') }}" target="_blank" rel="noopener" data-analytics-location="blog_newsletter">Получить подборку</a>
    @endif
</section>
