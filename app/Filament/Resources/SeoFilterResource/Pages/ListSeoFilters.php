<?php
namespace App\Filament\Resources\SeoFilterResource\Pages;
use App\Filament\Resources\SeoFilterResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
class ListSeoFilters extends ListRecords
{
    protected static string $resource = SeoFilterResource::class;
    protected function getHeaderActions(): array { return [CreateAction::make()]; }
}
