<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SeoPageResource\Pages;
use App\Models\SeoPage;
use Filament\Forms\Components\MarkdownEditor;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
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
use Illuminate\Support\Str;

class SeoPageResource extends Resource
{
    protected static ?string $model = SeoPage::class;

    public static function getNavigationIcon(): string   { return 'heroicon-o-magnifying-glass'; }
    public static function getNavigationGroup(): ?string { return 'SEO'; }
    public static function getNavigationLabel(): string  { return 'SEO-страницы'; }
    public static function getModelLabel(): string       { return 'SEO-страница'; }
    public static function getPluralModelLabel(): string { return 'SEO-страницы'; }
    public static function getNavigationSort(): int      { return 1; }

    public static function getNavigationBadge(): ?string
    {
        return (string) static::getModel()::count();
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([

            Section::make('Основная информация')
                ->columns(2)
                ->schema([
                    TextInput::make('title')
                        ->label('Заголовок страницы')
                        ->required()
                        ->maxLength(500)
                        ->live(onBlur: true)
                        ->afterStateUpdated(function (string $state, callable $set, $record) {
                            if (! $record?->slug) {
                                $set('slug', Str::slug($state));
                            }
                        })
                        ->columnSpanFull(),

                    TextInput::make('slug')
                        ->label('URL (slug)')
                        ->required()
                        ->unique(SeoPage::class, 'slug', ignoreRecord: true)
                        ->maxLength(255),

                    TextInput::make('h1')->label('H1')->maxLength(255)->nullable(),
                    Toggle::make('is_active')->label('Активна')->default(true),
                ]),

            Section::make('SEO мета')
                ->schema([
                    TextInput::make('meta_title')->label('Meta Title')->maxLength(255)->nullable(),
                    Textarea::make('meta_description')->label('Meta Description')->maxLength(500)->rows(3)->nullable(),
                    MarkdownEditor::make('seo_text')->label('SEO-текст')->nullable()->columnSpanFull(),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('title')
                    ->label('Заголовок')
                    ->searchable()
                    ->sortable()
                    ->limit(60)
                    ->description(fn (SeoPage $r): string => '/' . $r->slug),

                IconColumn::make('is_active')->label('Активна')->boolean()->alignCenter(),
                TextColumn::make('updated_at')->label('Изменена')->since()->sortable(),
            ])
            ->filters([TernaryFilter::make('is_active')->label('Активные')])
            ->actions([EditAction::make(), DeleteAction::make()])
            ->bulkActions([BulkActionGroup::make([DeleteBulkAction::make()])])
            ->striped();
    }

    public static function getRelations(): array { return []; }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListSeoPages::route('/'),
            'create' => Pages\CreateSeoPage::route('/create'),
            'edit'   => Pages\EditSeoPage::route('/{record}/edit'),
        ];
    }
}
