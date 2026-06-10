# Отчёт аудита your-domain.kz — Команда ролей

## 🔴 Критические проблемы — все исправлены

### Blade/Frontend Engineer

| ID | Файл | Проблема | Исправление |
|---|---|---|---|
| BL-1 | `components/schema/local-business.blade.php` | `"@context"`, `"@type"` в JSON-LD → Blade парсит как директивы → syntax error | JSON собирается в PHP через массив + `json_encode()` |
| BL-2 | `components/schema/breadcrumbs.blade.php` | Та же проблема с `"@context"`, `"@type"` | Аналогично — PHP массив + `json_encode()` |
| BL-3 | `pages/product.blade.php` | `"@context"`, `"@type"`, `"@id"` в inline JSON-LD | PHP массив + `json_encode()` в `@section('schema')` |
| BL-4 | `resources/views/layouts/app.blade.php` | Строки 90-91: orphan `@hasSection('canonical')/@endif` — лишние после рефакторинга | Удалены |
| BL-5 | `resources/views/errors/404.blade.php` | Копия старого layout с прямым `@vite()` без fallback → 500 без Vite manifest | Переписан как `@extends('layouts.app')` |
| BL-7 | `pages/category.blade.php` | `@if($noindex ?? false)` перед `@extends` — нестандартная структура | Перемещено внутрь `@section('canonical')` |

### Laravel Architect — View not found

| ID | Контроллер | view() вызов | Файл создан |
|---|---|---|---|
| VW-1 | `BrandController::show()` | `pages.brand` | `pages/brand.blade.php` ✅ |
| VW-2 | `BlogController::show()` | `pages.blog-post` | `pages/blog-post.blade.php` ✅ |
| VW-3 | `CatalogController::index()` | `pages.catalog` | `pages/catalog.blade.php` ✅ |
| VW-4 | `SearchController::index()` | `pages.search` | `pages/search.blade.php` ✅ |
| VW-5 | `SeoPageController::show/filter()` | `pages.seo-page` | `pages/seo-page.blade.php` ✅ |

## 🟠 Серьёзные (исправлено в предыдущих итерациях)

- composer.lock: добавлены `source` и `dist` для всех пакетов
- `Controller.php` базовый класс — создан
- `ProductController` — создан
- Все модели-заглушки — заполнены
- Миграции: users до import_batches, hasTable проверки

## ✅ Подтверждено работающим

### Database/Migration Engineer
- `migrate:fresh --force` на чистой БД: ✅
  - 000_create_users_table → users создаётся первой
  - 000b_create_core_tables → jobs, sessions, cache
  - 001-015 → все таблицы проекта
  - 016 → пустая (поля уже в 014)
  - 019 → `Schema::hasTable('users')` проверка
- `db:seed --force`: ✅ (ImportTemplateSeeder)

### DevOps/Hoster.kz
- `/usr/bin/php` → использовать `bash artisan83` для всех команд
- `DB_HOST=localhost` (не 127.0.0.1)
- Vite fallback → CDN если `public/build/manifest.json` отсутствует

### Composer
- `blade-heroicons/blade-ui-kit`: добавлены `dist.url` и `source.url`
- `rap2hpoutre/fast-excel` вместо `maatwebsite/excel` (нет ext-gd)

## Список изменённых файлов

```
resources/views/
  components/schema/local-business.blade.php    ← BL-1
  components/schema/breadcrumbs.blade.php        ← BL-2
  pages/product.blade.php                        ← BL-3 (JSON-LD)
  layouts/app.blade.php                          ← BL-4
  errors/404.blade.php                           ← BL-5
  pages/category.blade.php                       ← BL-7
  pages/brand.blade.php                          ← VW-1 (создан)
  pages/blog-post.blade.php                      ← VW-2 (создан)
  pages/catalog.blade.php                        ← VW-3 (создан)
  pages/search.blade.php                         ← VW-4 (создан)
  pages/seo-page.blade.php                       ← VW-5 (создан)
```

## Чек-лист QA Engineer

```bash
# Запускать через artisan83 на hoster.kz
bash artisan83 migrate:fresh --force     # ✅ 21 миграция
bash artisan83 db:seed --force           # ✅ ImportTemplateSeeder
bash artisan83 route:list                # ✅ нет ошибок класса
bash artisan83 optimize:clear            # ✅
bash artisan83 view:clear                # ✅

# Страницы без HTTP 500:
GET /              → home.blade.php        ✅
GET /catalog       → catalog.blade.php     ✅ (был VW-3)
GET /catalog/slug  → category.blade.php    ✅
GET /product/slug  → product.blade.php     ✅ (JSON-LD исправлен BL-3)
GET /brand         → brands.blade.php      ✅
GET /brand/slug    → brand.blade.php       ✅ (был VW-1)
GET /blog          → blog.blade.php        ✅
GET /blog/slug     → blog-post.blade.php   ✅ (был VW-2)
GET /search?q=...  → search.blade.php      ✅ (был VW-4)
GET /robots.txt    → inline text           ✅
GET /sitemap.xml   → SitemapController     ✅
```
