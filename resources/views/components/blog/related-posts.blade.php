@props(['posts'])

@if($posts->count())
<section class="blog-section">
    <div class="blog-section__head">
        <div>
            <h2>Читайте также</h2>
            <p>Ещё несколько материалов по выбору и эксплуатации кресел.</p>
        </div>
    </div>
    <div class="blog-related-posts">
        @foreach($posts as $post)
            <x-blog.article-card :post="$post" />
        @endforeach
    </div>
</section>
@endif
