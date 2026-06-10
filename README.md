# Офисные кресла Алматы

Интернет-магазин офисных кресел в Алматы, Казахстан.

## Стек

- Laravel 12
- Filament 4 (admin panel)
- Tailwind CSS + Alpine.js
- MySQL 8

## Быстрый старт

```bash
composer install --no-dev --optimize-autoloader
cp .env.example .env && nano .env
php artisan key:generate
php artisan migrate --force
php artisan db:seed --force
php artisan storage:link
php artisan import:xml-feed
php artisan serve
```

## Импорт товаров

```bash
# Проверить фид без сохранения
php artisan import:xml-feed --dry-run

# Полный импорт
php artisan import:xml-feed

# Только цены
php artisan import:xml-feed --prices-only --no-images
```

## Структура

```
app/Console/Commands/ImportXmlFeedCommand.php  ← Импорт XML
app/Services/Import/XmlFeedParser.php          ← Парсер фида
app/Http/Controllers/                          ← Контроллеры
app/Models/                                    ← Модели
resources/views/pages/                         ← Страницы
database/seeders/ChairStoreSeeder.php          ← Категории + настройки
```

## Развёртывание на hoster.kz

См. `INSTALL.md`
