<?php

namespace App\Http\Controllers;

use App\Models\BlogPost;
use App\Models\Product;
use App\Support\BlogContent;
use Illuminate\Http\Request;

class BlogController extends Controller
{
    public function index(Request $request)
    {
        $posts = BlogPost::active()
            ->latest('published_at')
            ->paginate(12);

        $currentPage = (int) $request->get('page', 1);
        $baseUrl     = url('/blog');

        $canonical = $baseUrl;
        $noindex = $currentPage > 1;

        $appName = config('app.name');

        $metaTitle = $currentPage > 1
            ? "Блог об офисных креслах — страница {$currentPage} | {$appName}"
            : "Блог об офисных креслах — советы и обзоры | {$appName}";

        $metaDesc = 'Статьи о выборе офисных кресел. Как выбрать эргономичное кресло, '
            . 'кресло для руководителя, обзоры и сравнения моделей. Советы экспертов.';

        $featuredPost = $currentPage === 1 ? $posts->first() : null;

        return view('pages.blog', compact(
            'posts',
            'canonical',
            'currentPage',
            'metaTitle',
            'metaDesc',
            'noindex',
            'featuredPost'
        ));
    }

    public function show(string $slug)
    {
        $post = BlogPost::active()
            ->where('slug', $slug)
            ->with([
                'products' => fn($q) => $q->active()
                    ->with([
                        'brand:id,name,slug',
                        'category:id,name,slug,parent_id',
                        'category.parent:id,slug',
                    ])
                    ->select('products.id', 'products.name', 'products.slug', 'products.sku',
                             'products.price', 'products.old_price', 'products.main_image',
                             'products.main_image_webp', 'products.main_image_alt',
                             'products.in_stock', 'products.is_hit', 'products.is_new',
                             'products.brand_id', 'products.category_id'),
            ])
            ->first();

        if (!$post) abort(404);

        $articleHtml = (string) $post->content;
        $readingTime = BlogContent::readingTime($post->content);
        $excerpt = BlogContent::excerpt($post);
        $faq = BlogContent::faq($post);

        $relatedProducts = $post->products;
        if ($relatedProducts->count() < 4) {
            $fallbackProducts = Product::active()
                ->inStock()
                ->with(['brand:id,name,slug', 'category:id,name,slug,parent_id', 'category.parent:id,slug'])
                ->whereNotIn('id', $relatedProducts->pluck('id'))
                ->orderByDesc('is_hit')
                ->orderByDesc('views')
                ->limit(8 - $relatedProducts->count())
                ->get([
                    'products.id', 'products.name', 'products.slug', 'products.sku',
                    'products.price', 'products.old_price', 'products.main_image',
                    'products.main_image_webp', 'products.main_image_alt',
                    'products.in_stock', 'products.is_hit', 'products.is_new',
                    'products.brand_id', 'products.category_id',
                ]);

            $relatedProducts = $relatedProducts->concat($fallbackProducts)->take(8);
        }

        $relatedPosts = BlogPost::active()
            ->where('id', '!=', $post->id)
            ->latest('published_at')
            ->limit(3)
            ->get(['id', 'title', 'slug', 'cover_image', 'cover_image_webp', 'cover_image_alt', 'meta_description', 'content', 'published_at']);

        $ogImage = $post->cover_image
            ? asset('storage/' . $post->cover_image)
            : asset('img/og-default.jpg');

        return view('pages.blog-post', compact(
            'post',
            'relatedPosts',
            'relatedProducts',
            'articleHtml',
            'readingTime',
            'excerpt',
            'faq',
            'ogImage'
        ) + ['ogType' => 'article']);
    }
}
