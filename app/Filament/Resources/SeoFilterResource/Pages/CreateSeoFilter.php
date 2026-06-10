<?php
namespace App\Filament\Resources\SeoFilterResource\Pages;
use App\Filament\Resources\SeoFilterResource;
use Filament\Resources\Pages\CreateRecord;
class CreateSeoFilter extends CreateRecord
{
    protected static string $resource = SeoFilterResource::class;
    protected function getRedirectUrl(): string { return $this->getResource()::getUrl('index'); }
}
