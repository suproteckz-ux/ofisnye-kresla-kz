# Ручной production-деплой GAGARIN SEO API

API создаёт только неопубликованные записи блога (`is_active=false`, `published_at=null`). Он не содержит операций изменения товаров, цен, остатков, SKU, заказов или удаления публикаций.

## Перед началом

1. Смержить подготовленный Laravel-коммит в `main` и выполнить push.
2. В Plesk создать резервную копию production-БД.
3. Открыть Terminal в корне Laravel-проекта. Фактический корень указан в Plesk → Laravel Toolkit; локальные SQLite-файлы и `.env` не загружать.

Добавить в production `.env`:

```dotenv
SEO_MANAGER_WRITE_TOKEN_SHA256=<скопируйте_из_DPAPI_OFFICE_CHAIRS_LARAVEL_API_TOKEN_sha256>
SEO_MANAGER_READ_TOKEN_SHA256=
SEO_MANAGER_ALLOW_LOCAL_HTTP=false
SEO_MANAGER_PUBLIC_URL=https://xn----8sbnbmoilhzgg4a5h.kz
```

Открытый токен хранится только в Windows DPAPI ГАГАРИН SEO. На сервер передаётся только SHA-256 выше.

## Команды в Plesk Terminal

```bash
set -euo pipefail
# Убедиться, что мы в корне Laravel-проекта
test -f artisan && test -f .env

git status --porcelain --untracked-files=no
git fetch origin
git checkout main
git pull --ff-only origin main

/opt/alt/php83/usr/bin/php artisan down
trap '/opt/alt/php83/usr/bin/php artisan up' EXIT

/opt/alt/php83/usr/bin/php composer install --no-dev --prefer-dist \
    --optimize-autoloader --no-interaction

/opt/alt/php83/usr/bin/php artisan migrate:status
/opt/alt/php83/usr/bin/php artisan migrate --force
/opt/alt/php83/usr/bin/php artisan optimize:clear
/opt/alt/php83/usr/bin/php artisan config:cache
/opt/alt/php83/usr/bin/php artisan route:list --path=api/seo-manager
/opt/alt/php83/usr/bin/php artisan up
trap - EXIT

curl --fail --silent --show-error \
    "https://xn----8sbnbmoilhzgg4a5h.kz/api/seo-manager/v1/status" \
    -H "Authorization: Bearer <токен_из_DPAPI_OFFICE_CHAIRS_LARAVEL_API_TOKEN>"
```

Если `git status` показывает изменения, если `migrate:status` выдаёт ошибку или любая команда завершилась с ненулевым кодом — остановитесь.

Ожидаются пять маршрутов:

```
GET|HEAD  api/seo-manager/v1/status
GET|HEAD  api/seo-manager/v1/catalog/{kind}
POST      api/seo-manager/v1/drafts
PUT       api/seo-manager/v1/drafts/{id}
POST      api/seo-manager/v1/proposals
```

После ручного деплоя сначала проверить `GET /api/seo-manager/v1/status` с production-токеном из DPAPI. Только после успешного HTTPS-ответа переключить настройки проекта на production. Автоматические генерацию и передачу оставить выключенными до отдельного контрольного цикла. Публикация остаётся ручной в Filament.
