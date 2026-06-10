<?php
namespace App\Filament\Resources\SeoFilterResource\Pages;
use App\Filament\Resources\SeoFilterResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
class EditSeoFilter extends EditRecord
{
    protected static string $resource = SeoFilterResource::class;
    protected function getHeaderActions(): array { return [DeleteAction::make()]; }
    protected function getRedirectUrl(): string { return $this->getResource()::getUrl('index'); }
}
