<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SettingResource\Pages;
use App\Models\Setting;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Cache;

class SettingResource extends Resource
{
    protected static ?string $model = Setting::class;

    public static function getNavigationIcon(): string   { return 'heroicon-o-cog-6-tooth'; }
    public static function getNavigationGroup(): ?string { return 'Система'; }
    public static function getNavigationLabel(): string  { return 'Настройки сайта'; }
    public static function getModelLabel(): string       { return 'Настройка'; }
    public static function getPluralModelLabel(): string { return 'Настройки'; }
    public static function getNavigationSort(): int      { return 10; }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Настройка')
                ->schema([
                    TextInput::make('key')
                        ->label('Ключ')
                        ->required()
                        ->unique(Setting::class, 'key', ignoreRecord: true)
                        ->maxLength(100),

                    Textarea::make('value')
                        ->label('Значение')
                        ->rows(4)
                        ->nullable(),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('key')
                    ->label('Ключ')
                    ->searchable()
                    ->sortable()
                    ->fontFamily('mono')
                    ->badge()
                    ->color('primary'),

                TextColumn::make('value')
                    ->label('Значение')
                    ->searchable()
                    ->limit(80)
                    ->wrap(),

                TextColumn::make('updated_at')->label('Обновлено')->since()->sortable(),
            ])
            ->defaultSort('key')
            ->actions([
                EditAction::make()->after(fn () => Cache::forget('site_settings')),
                DeleteAction::make()->after(fn () => Cache::forget('site_settings')),
            ])
            ->bulkActions([BulkActionGroup::make([
                DeleteBulkAction::make()->after(fn () => Cache::forget('site_settings')),
            ])])
            ->striped();
    }

    public static function getRelations(): array { return []; }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListSettings::route('/'),
            'create' => Pages\CreateSetting::route('/create'),
            'edit'   => Pages\EditSetting::route('/{record}/edit'),
        ];
    }
}
