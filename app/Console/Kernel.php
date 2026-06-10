<?php

namespace App\Console;

use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

/**
 * Laravel 12 использует routes/console.php для расписания задач.
 * Импорт XML запускается только вручную: php artisan import:xml-feed
 */
class Kernel extends ConsoleKernel
{
    protected function commands(): void
    {
        $this->load(__DIR__ . '/Commands');
    }
}
