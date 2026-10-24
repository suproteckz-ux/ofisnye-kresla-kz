<?php

namespace Tests\Feature;

use App\Models\BlogPost;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class SeoManagerApiTest extends TestCase
{
    private const TOKEN = 'office-local-test-token-not-a-real-secret-123456789';

    protected function setUp(): void
    {
        parent::setUp();
        config([
            'seo_manager.write_token_hash' => hash('sha256', self::TOKEN),
            'seo_manager.allow_local_http' => true,
            'seo_manager.public_url' => 'https://xn----8sbnbmoilhzgg4a5h.kz',
        ]);
        foreach (['2025_01_001_create_categories_table.php', '2025_01_002_create_brands_table.php',
            '2025_01_003_create_products_table.php', '2025_01_009_create_blog_posts_table.php',
            '2025_01_010_create_blog_post_product_table.php', '2026_10_24_000001_create_seo_manager_tables.php'] as $file) {
            (require database_path('migrations/'.$file))->up();
        }
        DB::table('categories')->insert(['id' => 1, 'name' => 'Офисные кресла', 'slug' => 'ofisnye-kresla', 'is_active' => true]);
        DB::table('brands')->insert(['id' => 1, 'name' => 'Test brand', 'slug' => 'test-brand', 'is_active' => true]);
        DB::table('products')->insert([
            'id' => 1, 'category_id' => 1, 'brand_id' => 1, 'name' => 'Эргономичное офисное кресло',
            'slug' => 'ergonomic-test-chair', 'sku' => 'CHAIR-1', 'price' => 100000,
            'quantity' => 2, 'in_stock' => true, 'is_active' => true, 'attributes' => json_encode(['Материал' => 'сетка']),
        ]);
    }

    private function headers(string $key = 'office-create-12345'): array
    {
        return ['Authorization' => 'Bearer '.self::TOKEN, 'Idempotency-Key' => $key];
    }

    private function payload(array $changes = []): array
    {
        return array_replace([
            'kind' => 'blog_post', 'title' => 'Как выбрать кресло для домашнего офиса',
            'slug' => 'kak-vybrat-kreslo-dlya-domashnego-ofisa',
            'meta_title' => 'Как выбрать кресло для домашнего офиса',
            'meta_description' => 'Практическое руководство по выбору офисного кресла по реальным характеристикам моделей.',
            'h1' => 'Как выбрать кресло для домашнего офиса', 'intro' => '<p>Проверьте посадку и регулировки.</p>',
            'seo_text' => '<h2>Посадка</h2><p>Сравните подтверждённые параметры.</p>',
            'faq' => [['question' => 'Что проверить?', 'answer' => 'Сверьте характеристики в карточке товара.']],
            'product_ids' => [1], 'category_ids' => [1],
            'canonical_url' => 'https://xn----8sbnbmoilhzgg4a5h.kz/blog/kak-vybrat-kreslo-dlya-domashnego-ofisa',
            'target_queries' => ['кресло для домашнего офиса'],
            'internal_links' => [['url' => 'https://xn----8sbnbmoilhzgg4a5h.kz/ofisnye-kresla', 'anchor' => 'Офисные кресла']],
            'image_recommendations' => [],
        ], $changes);
    }

    public function test_status_catalog_and_idempotent_inactive_draft(): void
    {
        $this->getJson('/api/seo-manager/v1/status', $this->headers())->assertOk()
            ->assertJson(['site_id' => 'office_chairs', 'content_type' => 'blog_post', 'drafts_write' => true]);
        $this->getJson('/api/seo-manager/v1/catalog/products', $this->headers())->assertOk()
            ->assertJsonPath('data.0.is_active', true)->assertJsonPath('data.0.in_stock', true);
        $before = (array) DB::table('products')->first();
        $response = $this->postJson('/api/seo-manager/v1/drafts', $this->payload(), $this->headers())
            ->assertCreated()->assertJson(['status' => 'draft', 'is_active' => false, 'site_id' => 'office_chairs']);
        $id = $response->json('id');
        $this->postJson('/api/seo-manager/v1/drafts', $this->payload(), $this->headers())
            ->assertCreated()->assertHeader('Idempotent-Replayed', 'true')->assertJson(['id' => $id]);
        $this->assertDatabaseCount('blog_posts', 1);
        $this->assertDatabaseHas('blog_posts', ['id' => $id, 'is_active' => false, 'published_at' => null]);
        $this->assertSame($before, (array) DB::table('products')->first());
    }

    public function test_rejects_auth_commerce_fields_and_inactive_products(): void
    {
        $this->getJson('/api/seo-manager/v1/status')->assertUnauthorized();
        foreach (['price', 'quantity', 'sku', 'is_active', 'orders'] as $field) {
            $this->postJson('/api/seo-manager/v1/drafts', $this->payload([$field => 1]), $this->headers('forbidden-'.$field))
                ->assertUnprocessable();
        }
        DB::table('products')->where('id', 1)->update(['is_active' => false]);
        $this->postJson('/api/seo-manager/v1/drafts', $this->payload(), $this->headers('inactive-product'))
            ->assertUnprocessable();
    }

    public function test_proposal_rejects_unknown_fields_and_external_links(): void
    {
        $id = $this->postJson('/api/seo-manager/v1/drafts', $this->payload(), $this->headers())->json('id');
        $base = ['target_type' => 'blog_post', 'target_id' => $id, 'proposal' => [
            'reason' => 'Проверить структуру статьи.',
        ]];
        $unknown = $base;
        $unknown['proposal']['price'] = 1;
        $this->postJson('/api/seo-manager/v1/proposals', $unknown, $this->headers('proposal-unknown'))
            ->assertUnprocessable();
        $external = $base;
        $external['proposal']['internal_links'] = [['url' => 'https://example.com/bad', 'anchor' => 'Внешняя ссылка']];
        $this->postJson('/api/seo-manager/v1/proposals', $external, $this->headers('proposal-external'))
            ->assertUnprocessable();
        $this->assertDatabaseCount('seo_manager_proposals', 0);
    }

    public function test_update_published_post_is_blocked_and_proposal_preserves_original(): void
    {
        $id = $this->postJson('/api/seo-manager/v1/drafts', $this->payload(), $this->headers())->json('id');
        $this->putJson('/api/seo-manager/v1/drafts/'.$id, $this->payload(['h1' => 'Обновлённый H1']), $this->headers('update-office-1'))->assertCreated();
        BlogPost::findOrFail($id)->update(['is_active' => true, 'published_at' => now()]);
        $this->putJson('/api/seo-manager/v1/drafts/'.$id, $this->payload(), $this->headers('update-office-2'))->assertConflict();
        $proposal = ['target_type' => 'blog_post', 'target_id' => $id, 'proposal' => [
            'meta_title' => 'Предлагаемый Title', 'proposed_sections' => ['Новый практический раздел'],
            'reason' => 'Проверить раскрытие интента по данным GSC.',
        ]];
        $this->postJson('/api/seo-manager/v1/proposals', $proposal, $this->headers('proposal-office'))->assertCreated();
        $this->postJson('/api/seo-manager/v1/proposals', $proposal, $this->headers('proposal-office'))
            ->assertCreated()->assertHeader('Idempotent-Replayed', 'true');
        $this->assertDatabaseCount('seo_manager_proposals', 1);
        $saved = DB::table('seo_manager_proposals')->first();
        $this->assertSame($this->payload()['title'], json_decode($saved->original, true)['title']);
    }
}
