<?php

namespace App\Filament\Widgets;

use App\Models\ImportBatch;
use App\Models\Lead;
use App\Models\Product;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class StatsOverviewWidget extends BaseWidget
{
    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        $totalProducts  = Product::count();
        $activeProducts = Product::where('is_active', true)->count();

        $newLeads   = Lead::where('status', 'new')->count();
        $totalLeads = Lead::count();

        $lastImport = ImportBatch::latest()->first();
        $importDesc = $lastImport
            ? $lastImport->created_at->diffForHumans() . ' · ' . $lastImport->status
            : 'Импорт не выполнялся';

        return [
            Stat::make('Товаров в каталоге', $totalProducts)
                ->description("{$activeProducts} активных")
                ->descriptionIcon('heroicon-m-cube')
                ->color('primary'),

            Stat::make('Новых заявок', $newLeads)
                ->description("Всего заявок: {$totalLeads}")
                ->descriptionIcon('heroicon-m-phone')
                ->color($newLeads > 0 ? 'warning' : 'success'),

            Stat::make('Последний импорт', $importDesc)
                ->descriptionIcon('heroicon-m-arrow-up-tray')
                ->color('gray'),
        ];
    }
}
