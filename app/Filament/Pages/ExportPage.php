<?php

namespace App\Filament\Pages;

use App\Exports\ProductsExport;
use Filament\Notifications\Notification;
use Filament\Pages\Page;

class ExportPage extends Page
{
    public static function getNavigationIcon(): string { return 'heroicon-o-arrow-down-tray'; }
    public static function getNavigationGroup(): ?string { return 'Импорт'; }
    public static function getNavigationLabel(): string { return 'Экспорт товаров'; }
    public function getTitle(): string { return 'Экспорт товаров'; }
    public static function getNavigationSort(): int { return 3; }

    protected string $view = 'filament.pages.export-page';

    public bool $activeOnly = true;

    public function exportProducts(): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        $filename = 'products_' . now()->format('Y-m-d_H-i') . '.xlsx';

        Notification::make()
            ->title('Файл формируется...')
            ->success()
            ->send();

        return (new ProductsExport($this->activeOnly))->download($filename);
    }
}
