<?php

namespace App\Filament\Resources;

use App\Filament\Resources\RedirectResource\Pages;
use App\Models\Redirect;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Cache;

class RedirectResource extends Resource
{
    protected static ?string $model = Redirect::class;

    public static function getNavigationIcon(): string   { return 'heroicon-o-arrow-right-circle'; }
    public static function getNavigationGroup(): ?string { return 'Система'; }
    public static function getNavigationLabel(): string  { return 'Редиректы'; }
    public static function getModelLabel(): string       { return 'Редирект'; }
    public static function getPluralModelLabel(): string { return 'Редиректы'; }
    public static function getNavigationSort(): int      { return 11; }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Редирект 301')
                ->schema([
                    TextInput::make('from_url')
                        ->label('Откуда (старый URL)')
                        ->required()
                        ->maxLength(500)
                        ->unique(Redirect::class, 'from_url', ignoreRecord: true),

                    TextInput::make('to_url')
                        ->label('Куда (новый URL)')
                        ->required()
                        ->maxLength(500),

                    Toggle::make('is_active')->label('Активен')->default(true),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('from_url')
                    ->label('Откуда')
                    ->searchable()
                    ->fontFamily('mono')
                    ->limit(60),

                TextColumn::make('to_url')
                    ->label('Куда')
                    ->searchable()
                    ->fontFamily('mono')
                    ->limit(60),

                IconColumn::make('is_active')->label('Активен')->boolean()->alignCenter(),

                TextColumn::make('updated_at')->label('Изменён')->since()->sortable(),
            ])
            ->filters([TernaryFilter::make('is_active')->label('Активные')])
            ->actions([
                EditAction::make()->after(fn () => Cache::forget('active_redirects')),
                DeleteAction::make()->after(fn () => Cache::forget('active_redirects')),
            ])
            ->bulkActions([BulkActionGroup::make([
                DeleteBulkAction::make()->after(fn () => Cache::forget('active_redirects')),
            ])])
            ->striped();
    }

    public static function getRelations(): array { return []; }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListRedirects::route('/'),
            'create' => Pages\CreateRedirect::route('/create'),
            'edit'   => Pages\EditRedirect::route('/{record}/edit'),
        ];
    }
}
