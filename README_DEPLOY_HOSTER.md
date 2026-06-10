# Деплой your-domain.kz на Hoster.kz

## ⚠️ Важно: не использовать кнопки Laravel Toolkit

Laravel Toolkit в Plesk использует `/usr/bin/php` (без pdo_mysql).
Сайт работает через `/opt/alt/php83/usr/bin/php` (с pdo_mysql).
Все artisan-команды запускать только через `artisan83` или полный путь.

---

## Первый деплой

```bash
cd /var/www/vhosts/your-domain.kz/test.your-domain.kz

# 1. Директории storage
mkdir -p storage/framework/{views,sessions,cache/data,testing}
mkdir -p storage/logs storage/backups bootstrap/cache
chmod -R 775 storage bootstrap/cache

# 2. .env
cp .env.example .env
# Заполнить DB_*, APP_URL, APP_KEY

# 3. APP_KEY
bash artisan83 key:generate

# 4. composer install (БЕЗ Toolkit!)
/opt/alt/php83/usr/bin/php /usr/local/bin/composer install \
    --no-dev --optimize-autoloader --no-interaction

# 5. Миграции
bash artisan83 migrate --force

# 6. Сидеры
bash artisan83 db:seed --force

# 7. Первый администратор
bash artisan83 make:filament-user

# 8. storage:link
bash artisan83 storage:link

# 9. Frontend (если есть Node.js)
npm install && npm run build

# 10. Кэш
bash artisan83 optimize
```

---

## .env для Hoster.kz

```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://test.your-domain.kz

DB_CONNECTION=mysql
DB_HOST=localhost         # ВАЖНО: localhost, не 127.0.0.1
DB_PORT=3306
DB_DATABASE=p-352011_chairs-almaty_test
DB_USERNAME=p-352011_chairs-almaty_test
DB_PASSWORD=ВАШ_ПАРОЛЬ

CACHE_STORE=file
QUEUE_CONNECTION=database
SESSION_DRIVER=file
SESSION_SECURE_COOKIE=true
```

---

## Document Root

В Plesk → Hosting Settings → Document Root указать:
```
/var/www/vhosts/your-domain.kz/test.your-domain.kz/public
```

---

## Frontend без npm (режим CDN)

Если npm/Node.js недоступны — layout автоматически подключит
Tailwind CDN и Alpine.js CDN вместо Vite-бандла.
Это работает, но медленнее. Для production запустите:
```bash
npm install && npm run build
```

---

## Artisan без wrapper

```bash
/opt/alt/php83/usr/bin/php artisan <команда>

# Или через alias:
alias php='/opt/alt/php83/usr/bin/php'
php artisan migrate --force
```

---

## Частые ошибки

| Ошибка | Причина | Решение |
|---|---|---|
| `could not find driver` | Запуск через /usr/bin/php | Использовать /opt/alt/php83/usr/bin/php |
| `Invalid cache path` | Нет storage/framework/* | mkdir -p storage/framework/{views,sessions,cache/data} |
| `Class Controller not found` | Нет base Controller | Файл app/Http/Controllers/Controller.php уже в проекте |
| `Vite manifest not found` | Не запущен npm run build | Layout автоматически переключится на CDN |
| `pdo_oci.so warning` | Oracle модуль не нужен | Игнорировать — это warning, не ошибка |
