# Инструкция по установке — Магазин офисных кресел

---

## ⚠️ Важно: vendor не входит в архив

Папка `vendor/` (PHP-зависимости, ~100 MB) **не включена** в архив.
После распаковки **обязательно** выполните:

```bash
composer install --no-dev --optimize-autoloader
```

Без этого `php artisan` не запустится (ошибка `vendor/autoload.php: No such file or directory`).

---

## Системные требования

- PHP 8.2+ (рекомендуется 8.3)
- MySQL 8.0+
- Composer 2.x
- Расширения PHP: mbstring, pdo_mysql, xml, zip, gd или imagick

---

## Установка (локально / VPS)

```bash
# 1. Распаковать
unzip chairs-almaty.zip && cd chairs

# 2. ОБЯЗАТЕЛЬНО: установить PHP-зависимости
composer install --no-dev --optimize-autoloader

# 3. Настроить окружение
cp .env.example .env
nano .env
# Заполнить: APP_URL, DB_*, IMPORT_XML_URL, ADMIN_EMAIL, ADMIN_PASSWORD

# 4. Ключ приложения
php artisan key:generate

# 5. Зарегистрировать пакеты (ОБЯЗАТЕЛЬНО после key:generate)
php artisan package:discover --ansi
php artisan filament:upgrade

# 6. Миграции (создать таблицы)
php artisan migrate --force

# 6. Сидеры (категории + настройки + первый admin)
php artisan db:seed --force

# 7. Символическая ссылка storage
php artisan storage:link

# 8. Первый импорт товаров
php artisan import:xml-feed --dry-run   # проверка (ничего не сохраняет)
php artisan import:xml-feed              # полный импорт

# 9. Кэширование (для production)
php artisan config:cache
php artisan route:cache
php artisan view:cache

# 10. Запустить сервер (локально)
php artisan serve
# Откройте: http://localhost:8000
# Админка:  http://localhost:8000/admin
```

---

## Установка на hoster.kz (PHP 8.3)

На hoster.kz PHP 8.3 запускается через специальный wrapper.
В архиве уже есть файл `artisan83`.

```bash
# 1. Composer (PHP 8.3 на hoster.kz)
php8.3 /usr/local/bin/composer install --no-dev --optimize-autoloader

# 2. .env
cp .env.example .env && nano .env
# DB_HOST=localhost  (не 127.0.0.1!)
# APP_URL=https://your-domain.kz  (без слеша в конце)

# 3. Инициализация (используем artisan83 вместо php artisan)
bash artisan83 key:generate
bash artisan83 package:discover --ansi
bash artisan83 filament:upgrade
bash artisan83 migrate --force
bash artisan83 db:seed --force
bash artisan83 storage:link

# 4. Импорт товаров
bash artisan83 import:xml-feed --dry-run
bash artisan83 import:xml-feed

# 5. Кэширование
bash artisan83 config:cache
bash artisan83 route:cache
bash artisan83 view:cache
bash artisan83 optimize
```

### Крон на hoster.kz (только для прогрева кэша и бэкапа)

В cPanel → Cron Jobs добавить одну строку:
```
* * * * * /usr/bin/php8.3 /home/USERNAME/public_html/artisan83 schedule:run >> /dev/null 2>&1
```

> **⚠️ Автоматический XML-импорт НЕ настроен.**
> Импорт запускается только вручную (см. ниже).

---

## Импорт товаров (только ручной)

### Через командную строку

```bash
# Проверка фида (ничего не сохраняет)
php artisan import:xml-feed --dry-run

# Полный импорт (с загрузкой фото)
php artisan import:xml-feed

# Только обновить цены и наличие (без фото, быстро)
php artisan import:xml-feed --prices-only --no-images

# Из другого URL
php artisan import:xml-feed --url="https://example.com/feed.xml"
```

**После импорта выводится отчёт:**
```
┌─────────────────────────────────────────────┐
│            Отчёт об импорте                 │
└─────────────────────────────────────────────┘
 Показатель            │ Значение
 Найдено в фиде        │ 150
 ✅ Создано новых      │ 148
 🔄 Обновлено          │ 2
 ⏭️ Пропущено          │ 0
 ❌ Ошибок             │ 0
 🖼️ Загружено фото     │ 146
 ⏱️ Время выполнения   │ 47.3 сек
 🗃️ Batch ID           │ 1
```

### Через админку Filament

1. Откройте `/admin`
2. В левом меню: **Импорт → Импорт XML фида**
3. Нажмите «Предварительный просмотр» — увидите список товаров
4. Нажмите «Импортировать товары» — начнётся импорт
5. После завершения — отчёт прямо на странице

---

## Настройка контактов

После установки обновите данные в **Admin → Настройки**:

| Ключ | Значение |
|---|---|
| `phone` | +7 700 000 00 00 |
| `whatsapp` | 77000000000 |
| `address` | г. Алматы, ул. Примерная, 1 |
| `working_hours` | Пн–Сб: 9:00–18:00 |
| `email` | info@your-domain.kz |

---

## Чек-лист после установки

### Обязательно
- [ ] Обновить контакты в Admin → Настройки
- [ ] Запустить `import:xml-feed` (импортировать товары)
- [ ] Добавить `public/img/og-default.jpg` (1200×630 px, OG-фото)
- [ ] Добавить `public/favicon.ico`
- [ ] Настроить SSL/HTTPS
- [ ] Отправить sitemap в Google Search Console: `/sitemap.xml`
- [ ] Отправить sitemap в Яндекс.Вебмастер

### SEO
- [ ] Добавить Google Analytics ID в Настройки → `google_analytics`
- [ ] Добавить Яндекс.Метрику → `yandex_metrika`
- [ ] Проверить Schema.org: https://search.google.com/test/rich-results
- [ ] Проверить PageSpeed: https://pagespeed.web.dev

---

## Основные команды

```bash
# Очистить все кэши
php artisan optimize:clear

# Посмотреть все маршруты
php artisan route:list

# Статус очереди
php artisan queue:work --sleep=3 --tries=3

# Просмотр логов
tail -f storage/logs/laravel.log
```

---

## Что НЕ входит в архив

| Что | Почему | Что делать |
|---|---|---|
| `vendor/` | 100 MB, ставится через composer | `composer install` |
| `node_modules/` | Не нужен — фронтенд через CDN | Ничего |
| `.env` | Содержит секреты | Создать из `.env.example` |
| Изображения товаров | Загружаются при импорте | `import:xml-feed` |

---

## Структура проекта

```
app/
  Console/Commands/ImportXmlFeedCommand.php  ← Команда импорта (ручная)
  Filament/Pages/XmlImportPage.php           ← Кнопка импорта в админке
  Services/Import/XmlFeedParser.php          ← Парсер Google Merchant XML
  Services/Import/FullProductImporter.php    ← Создание/обновление товаров
  Services/Import/ImageDownloader.php        ← Загрузка + WebP конвертация
  Http/Controllers/                          ← Все контроллеры
  Models/                                    ← Eloquent модели
database/
  migrations/                               ← 19 миграций (все таблицы)
  seeders/ChairStoreSeeder.php              ← Категории кресел + настройки
resources/views/
  layouts/app.blade.php                     ← Главный лейаут
  pages/home.blade.php                      ← Главная страница
  pages/category.blade.php                  ← Категория + фильтры по атрибутам
  pages/product.blade.php                   ← Карточка товара + галерея
config/import.php                           ← Настройки XML импорта
routes/web.php                              ← Все маршруты
routes/console.php                          ← Расписание (без авто-импорта)
```
