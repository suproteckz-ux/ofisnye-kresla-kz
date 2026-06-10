<?php
namespace App\Filament\Resources\LeadResource\Pages;
use App\Filament\Resources\LeadResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
class EditLead extends EditRecord
{
    protected static string $resource = LeadResource::class;
    protected function getHeaderActions(): array { return [DeleteAction::make()]; }
    protected function getRedirectUrl(): string { return $this->getResource()::getUrl('index'); }
}
