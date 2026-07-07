@props(['items' => []])

@if(count($items))
<section class="blog-section">
    <div class="blog-section__head">
        <div>
            <h2>Вопросы и ответы</h2>
            <p>Коротко по главным вопросам из статьи.</p>
        </div>
    </div>
    <div class="blog-faq">
        @foreach($items as $item)
        <details>
            <summary>{{ $item['question'] }}</summary>
            <div>{!! nl2br(e($item['answer'])) !!}</div>
        </details>
        @endforeach
    </div>
</section>
@endif
