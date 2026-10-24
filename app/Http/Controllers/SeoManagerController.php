<?php

namespace App\Http\Controllers;

use App\Models\BlogPost;
use App\Models\Category;
use App\Models\Product;
use App\Services\SeoManager\IdempotentWrite;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class SeoManagerController extends Controller
{
    public function status(Request $request)
    {
        return response()->json([
            'status' => 'ready', 'api_version' => 1, 'site_id' => 'office_chairs',
            'content_type' => 'blog_post', 'drafts_write' => (bool) $request->attributes->get('seo_can_write'),
        ]);
    }

    public function catalog(Request $request, string $kind)
    {
        abort_unless(in_array($kind, ['categories', 'products', 'posts'], true), 404);
        $request->validate(['page' => 'sometimes|integer|min:1', 'per_page' => 'sometimes|integer|min:1|max:500']);
        $query = match ($kind) {
            'categories' => Category::query()->with('parent:id,slug')->withCount(['products as available_products_count' => fn ($q) => $q->active()->inStock()]),
            'products' => Product::query()->with(['brand:id,name,slug', 'category:id,name,slug,parent_id']),
            'posts' => BlogPost::query()->with('products:id,name,slug,is_active,in_stock')->orderByDesc('published_at'),
        };
        if ($kind !== 'posts') {
            $query->orderBy('id');
        }
        $paginator = $query->paginate($request->integer('per_page', 200));
        $items = $paginator->getCollection()->map(function ($entity) use ($kind) {
            if ($kind === 'posts') {
                return [
                    'id' => $entity->id, 'title' => $entity->title, 'slug' => $entity->slug,
                    'h1' => $entity->h1, 'meta_title' => $entity->meta_title,
                    'meta_description' => $entity->meta_description, 'content' => $entity->content,
                    'faq' => $entity->faq, 'cover_image' => $entity->cover_image,
                    'is_active' => $entity->is_active, 'status' => $entity->is_active ? 'published' : 'draft',
                    'published_at' => $entity->published_at?->toIso8601String(),
                    'updated_at' => $entity->updated_at?->toIso8601String(),
                    'product_ids' => $entity->products->modelKeys(), 'url' => $this->publicUrl('/blog/'.$entity->slug),
                    'canonical_url' => $this->publicUrl('/blog/'.$entity->slug),
                ];
            }
            if ($kind === 'products') {
                return [
                    'id' => $entity->id, 'name' => $entity->name, 'slug' => $entity->slug,
                    'sku' => $entity->sku, 'url' => $this->publicUrl(parse_url($entity->url, PHP_URL_PATH) ?: '/product/'.$entity->slug), 'category_id' => $entity->category_id,
                    'category_ids' => array_values(array_filter([$entity->category_id])),
                    'category' => $entity->category?->only(['id', 'name', 'slug']),
                    'brand' => $entity->brand?->only(['id', 'name', 'slug']),
                    'image' => $entity->main_image_webp ?: $entity->main_image,
                    'attributes' => $entity->attributes_array, 'description' => $entity->description,
                    'short_description' => $entity->short_description, 'price' => $entity->price,
                    'quantity' => $entity->quantity, 'in_stock' => $entity->in_stock,
                    'is_active' => $entity->is_active, 'updated_at' => $entity->updated_at?->toIso8601String(),
                ];
            }

            return [
                'id' => $entity->id, 'name' => $entity->name, 'slug' => $entity->slug,
                'parent_id' => $entity->parent_id, 'is_active' => $entity->is_active,
                'meta_title' => $entity->meta_title, 'meta_description' => $entity->meta_description,
                'h1' => $entity->h1, 'url' => $this->publicUrl(parse_url($entity->url, PHP_URL_PATH) ?: '/'.$entity->slug),
                'available_products_count' => $entity->available_products_count,
                'updated_at' => $entity->updated_at?->toIso8601String(),
            ];
        });

        return response()->json(['data' => $items, 'meta' => [
            'current_page' => $paginator->currentPage(), 'last_page' => $paginator->lastPage(),
            'total' => $paginator->total(), 'per_page' => $paginator->perPage(),
            'generated_at' => now()->toIso8601String(),
        ]]);
    }

    public function create(Request $request, IdempotentWrite $writer)
    {
        return $writer->run($request, 'blog_draft.create', fn () => $this->saveDraft($request));
    }

    public function update(Request $request, int $id, IdempotentWrite $writer)
    {
        return $writer->run($request, 'blog_draft.update', fn () => $this->saveDraft($request, $id));
    }

    private function saveDraft(Request $request, ?int $id = null): array
    {
        $post = $id ? BlogPost::whereKey($id)->lockForUpdate()->firstOrFail() : new BlogPost;
        if ($id) {
            abort_if($post->is_active, 409, 'Published posts cannot be changed by this API.');
            abort_unless(DB::table('seo_manager_drafts')->where('blog_post_id', $id)->exists(), 403, 'This draft is not managed by SEO Manager.');
        }
        $allowed = ['kind', 'title', 'slug', 'meta_title', 'meta_description', 'h1', 'intro', 'seo_text', 'faq',
            'product_ids', 'category_ids', 'canonical_url', 'target_queries', 'image_recommendations', 'internal_links'];
        $this->rejectUnknown($request->all(), $allowed);
        $data = $request->validate([
            'kind' => 'sometimes|in:blog_post,page',
            'title' => 'required|string|max:500',
            'slug' => ['required', 'string', 'max:190', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/', Rule::unique('blog_posts')->ignore($id)],
            'meta_title' => 'required|string|max:255', 'meta_description' => 'required|string|max:500',
            'h1' => 'required|string|max:255', 'intro' => 'nullable|string|max:2000',
            'seo_text' => 'required|string|max:120000', 'faq' => 'sometimes|array|max:20',
            'faq.*' => 'array:question,answer', 'faq.*.question' => 'required|string|max:500',
            'faq.*.answer' => 'required|string|max:3000',
            'product_ids' => 'sometimes|array|max:50',
            'product_ids.*' => ['integer', 'distinct', Rule::exists('products', 'id')->where('is_active', true)->where('in_stock', true)],
            'category_ids' => 'sometimes|array|max:20',
            'canonical_url' => 'required|url|max:500', 'target_queries' => 'sometimes|array|max:100',
            'target_queries.*' => 'string|max:255', 'image_recommendations' => 'sometimes|array|max:20',
            'image_recommendations.*' => 'string|max:1000', 'internal_links' => 'sometimes|array|max:50',
            'internal_links.*' => 'array:url,anchor', 'internal_links.*.url' => 'required|url|max:500',
            'internal_links.*.anchor' => 'required|string|max:255',
        ]);
        if (BlogPost::whereRaw('LOWER(title) = ?', [mb_strtolower($data['title'])])->when($id, fn ($q) => $q->where('id', '!=', $id))->exists()) {
            throw ValidationException::withMessages(['title' => 'A blog post with this title already exists.']);
        }
        $expected = rtrim((string) config('seo_manager.public_url'), '/').'/blog/'.$data['slug'];
        if (rtrim($data['canonical_url'], '/') !== $expected) {
            throw ValidationException::withMessages(['canonical_url' => 'Canonical must match the stable blog URL.']);
        }
        foreach ($data['internal_links'] ?? [] as $link) {
            $this->validateInternalUrl($link['url']);
        }
        $post->fill([
            'title' => strip_tags($data['title']), 'slug' => $data['slug'],
            'meta_title' => strip_tags($data['meta_title']),
            'meta_description' => strip_tags($data['meta_description']), 'h1' => strip_tags($data['h1']),
            'content' => $this->sanitizeHtml(($data['intro'] ?? '')."\n".$data['seo_text']),
            'faq' => collect($data['faq'] ?? [])->map(fn ($item) => [
                'question' => strip_tags($item['question']), 'answer' => strip_tags($item['answer']),
            ])->all(),
        ]);
        $post->is_active = false;
        $post->published_at = null;
        $post->save();
        $post->products()->sync($data['product_ids'] ?? []);
        DB::table('seo_manager_drafts')->updateOrInsert(['blog_post_id' => $post->id], [
            'metadata' => json_encode(collect($data)->only(['target_queries', 'image_recommendations', 'internal_links'])->all(), JSON_THROW_ON_ERROR),
            'created_at' => now(), 'updated_at' => now(),
        ]);

        return [
            'id' => $post->id, 'kind' => 'blog_post', 'site_id' => 'office_chairs',
            'status' => 'draft', 'is_active' => false, 'url' => $this->publicUrl('/blog/'.$post->slug),
            'admin_url' => url('/admin/blog-posts/'.$post->id.'/edit'),
        ];
    }

    public function proposal(Request $request, IdempotentWrite $writer)
    {
        return $writer->run($request, 'blog_proposal.create', function () use ($request) {
            $this->rejectUnknown($request->all(), ['target_type', 'target_id', 'proposal']);
            $this->rejectUnknown((array) $request->input('proposal', []), [
                'title', 'meta_title', 'meta_description', 'h1', 'seo_text',
                'proposed_sections', 'internal_links', 'reason',
            ]);
            $data = $request->validate([
                'target_type' => 'required|in:blog_post', 'target_id' => 'required|integer|min:1|exists:blog_posts,id',
                'proposal' => 'required|array', 'proposal.title' => 'sometimes|string|max:500',
                'proposal.meta_title' => 'sometimes|string|max:255', 'proposal.meta_description' => 'sometimes|string|max:500',
                'proposal.h1' => 'sometimes|string|max:255', 'proposal.seo_text' => 'sometimes|string|max:120000',
                'proposal.proposed_sections' => 'sometimes|array|max:30', 'proposal.proposed_sections.*' => 'string|max:1000',
                'proposal.internal_links' => 'sometimes|array|max:50',
                'proposal.internal_links.*' => 'array:url,anchor',
                'proposal.internal_links.*.url' => 'required|url|max:500',
                'proposal.internal_links.*.anchor' => 'required|string|max:255',
                'proposal.reason' => 'required|string|max:6000',
            ]);
            $post = BlogPost::findOrFail($data['target_id']);
            $proposal = $data['proposal'];
            foreach (['title', 'meta_title', 'meta_description', 'h1', 'reason'] as $field) {
                if (isset($proposal[$field])) {
                    $proposal[$field] = strip_tags($proposal[$field]);
                }
            }
            if (isset($proposal['seo_text'])) {
                $proposal['seo_text'] = $this->sanitizeHtml($proposal['seo_text']);
            }
            if (isset($proposal['proposed_sections'])) {
                $proposal['proposed_sections'] = array_map(fn ($value) => strip_tags($value), $proposal['proposed_sections']);
            }
            foreach ($proposal['internal_links'] ?? [] as &$link) {
                $this->validateInternalUrl($link['url']);
                $link['anchor'] = strip_tags($link['anchor']);
            }
            unset($link);
            $id = DB::table('seo_manager_proposals')->insertGetId([
                'blog_post_id' => $post->id,
                'original' => json_encode($post->only(['title', 'slug', 'h1', 'meta_title', 'meta_description', 'content', 'faq']), JSON_THROW_ON_ERROR),
                'proposal' => json_encode($proposal, JSON_THROW_ON_ERROR), 'status' => 'proposed',
                'created_at' => now(), 'updated_at' => now(),
            ]);

            return [
                'id' => $id, 'kind' => 'proposal', 'site_id' => 'office_chairs', 'status' => 'proposed',
                'target_id' => $post->id, 'admin_url' => url('/admin/blog-posts/'.$post->id.'/edit'),
            ];
        });
    }

    private function rejectUnknown(array $data, array $allowed): void
    {
        if ($extra = array_diff(array_keys($data), $allowed)) {
            throw ValidationException::withMessages(['payload' => 'Unsupported fields: '.implode(', ', $extra)]);
        }
    }

    private function validateInternalUrl(string $url): void
    {
        $parts = parse_url($url);
        $host = strtolower((string) ($parts['host'] ?? ''));
        abort_unless(($parts['scheme'] ?? '') === 'https' && $host === 'xn----8sbnbmoilhzgg4a5h.kz'
            && ! isset($parts['user'], $parts['pass'], $parts['port'], $parts['query'], $parts['fragment']), 422, 'Use an internal HTTPS URL without parameters.');
    }

    private function publicUrl(string $path): string
    {
        return rtrim((string) config('seo_manager.public_url'), '/').'/'.ltrim($path, '/');
    }

    private function sanitizeHtml(string $value): string
    {
        $value = strip_tags($value, '<p><h2><h3><ul><ol><li><strong><em><br><a>');
        $value = preg_replace('/\s(?:on\w+|style|class|id)\s*=\s*(["\']).*?\1/isu', '', $value) ?? $value;
        $value = preg_replace('/href\s*=\s*(["\'])\s*(?:javascript|data):.*?\1/isu', '', $value) ?? $value;

        return trim($value);
    }
}
