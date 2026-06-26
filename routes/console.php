<?php

use Illuminate\Support\Facades\Schedule;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

// ═══════════════════════════════════════════════════════════════
// Расписание задач — Офисные кресла Алматы
//
// ВНИМАНИЕ: Автоматический импорт XML НЕ настроен.
// Импорт выполняется только вручную:
//   php artisan import:xml-feed
// или через кнопку в Filament-админке.
// ═══════════════════════════════════════════════════════════════

// ── Прогрев кэша главной страницы (каждые 30 мин, с 8 до 22) ─
Schedule::call(function () {
    \App\Services\CacheService::forgetHomepage();
    \App\Services\CacheService::homepageCategories();
    \App\Services\CacheService::homepageHits();
    \App\Services\CacheService::homepageNewProducts();
    \App\Services\CacheService::homepageBrands();
})->everyThirtyMinutes()
  ->between('8:00', '22:00')
  ->name('cache:warmup-homepage')
  ->withoutOverlapping();

Schedule::command('marketradar:sync --no-photos')
    ->everyThreeHours()
    ->name('marketradar:sync-prices-stock')
    ->withoutOverlapping();

// ── Мониторинг зависших импортов (раз в час) ──────────────────
Schedule::call(function () {
    $stuck = \App\Models\ImportBatch::where('status', 'processing')
        ->where('started_at', '<', now()->subHours(2))
        ->get();

    foreach ($stuck as $batch) {
        $batch->update(['status' => 'failed', 'finished_at' => now()]);
        Log::warning("Зависший импорт #{$batch->id} помечен как failed");
    }
})->hourly()->name('import:check-stuck')->withoutOverlapping();

// ── Ежедневный бэкап БД ───────────────────────────────────────
Schedule::call(function () {
    $backupDir = storage_path('backups');
    if (!is_dir($backupDir)) mkdir($backupDir, 0750, true);

    $host   = config('database.connections.mysql.host');
    $port   = config('database.connections.mysql.port', '3306');
    $dbname = config('database.connections.mysql.database');
    $user   = config('database.connections.mysql.username');
    $pass   = config('database.connections.mysql.password');

    $filename = 'backup_' . now()->format('Y-m-d') . '.sql.gz';
    $filepath = "{$backupDir}/{$filename}";

    $mycnf = tempnam(sys_get_temp_dir(), 'mysql_');
    file_put_contents($mycnf, "[client]\npassword={$pass}\n");
    chmod($mycnf, 0600);

    exec(sprintf(
        'mysqldump --defaults-extra-file=%s -h%s -P%s -u%s %s | gzip > %s 2>&1',
        escapeshellarg($mycnf), escapeshellarg($host), escapeshellarg($port),
        escapeshellarg($user), escapeshellarg($dbname), escapeshellarg($filepath)
    ), $out, $code);
    @unlink($mycnf);

    if ($code === 0) {
        Log::info("Backup: создан {$filename}");
        // Удалить бэкапы старше 14 дней
        foreach (glob("{$backupDir}/backup_*.sql.gz") as $f) {
            if (filemtime($f) < now()->subDays(14)->timestamp) @unlink($f);
        }
    } else {
        Log::error('Backup: ошибка', ['output' => implode("\n", $out)]);
    }
})->dailyAt('02:00')->name('db:backup')->withoutOverlapping();

// ── Очистка старых записей импорта (раз в месяц) ─────────────
Schedule::call(function () {
    $count = \App\Models\ImportBatch::where('created_at', '<', now()->subDays(90))
        ->where('status', 'done')->count();
    \App\Models\ImportBatch::where('created_at', '<', now()->subDays(90))
        ->where('status', 'done')->delete();
    if ($count > 0) Log::info("Удалено {$count} старых записей импорта");
})->monthly()->name('import:cleanup')->withoutOverlapping();

// ── Очистка failed jobs (раз в неделю) ───────────────────────
Schedule::call(function () {
    DB::table('failed_jobs')
        ->where('failed_at', '<', now()->subDays(7)->toDateTimeString())
        ->delete();
})->weekly()->name('queue:cleanup-failed')->withoutOverlapping();
