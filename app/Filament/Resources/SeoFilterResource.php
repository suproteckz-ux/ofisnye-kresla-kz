<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SeoFilterResource\Pages;
use App\Models\SeoFilter;
use Filament\Forms\Components\MarkdownEditor;
use Filament\Forms\Components\Select;
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
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Support\Str;

class SeoFilterResource extends Resource
{
    protected static ?string $model = SeoFilter::class;

    public static function getNavigationIcon(): string   { return 'heroicon-o-funnel'; }
    public static function getNavigationGroup(): ?string { return 'SEO'; }
    public static function getNavigationLabel(): string  { return 'SEO-фильтры'; }
    public static function getModelLabel(): string       { return 'SEO-фильтр'; }
    public static function getPluralModelLabel(): string { return 'SEO-фильтры'; }
    public static function getNavigationSort(): int      { return 2; }

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
                    TextInput::make('name')
                        ->label('Название фильтра')
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
                        ->label('URL slug')
                        ->required()
                        ->unique(SeoFilter::class, 'slug', ignoreRecord: true)
                        ->maxLength(255),

                    Select::make('category_id')
                        ->label('Категория')
                        ->relationship('category', 'name')
                        ->searchable()
                        ->preload()
                        ->nullable(),

                    Select::make('brand_id')
                        ->label('Бренд')
                        ->relationship('brand', 'name')
                        ->searchable()
                        ->preload()
                        ->nullable(),

                    Toggle::make('is_active')->label('Активен')->default(true),
                    Toggle::make('is_indexed')->label('Индексируется')->default(true),
                ]),

            Section::make('SEO')
                ->collapsed()
                ->schema([
                    TextInput::make('h1')->label('H1')->maxLength(255)->nullable(),
                    TextInput::make('meta_title')->label('Meta Title')->maxLength(255)->nullable(),
                    Textarea::make('meta_description')->label('Meta Description')->maxLength(500)->rows(3)->nullable(),
                    MarkdownEditor::make('seo_text')->label('SEO-текст')->nullable(),
                    TextInput::make('canonical_url')->label('Canonical URL')->url()->maxLength(500)->nullable(),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Название')
                    ->searchable()
                    ->sortable()
                    ->limit(50)
                    ->description(fn (SeoFilter $r): string => (string) $r->slug),

                TextColumn::make('category.name')->label('Категория')->badge()->color('primary'),
                TextColumn::make('brand.name')->label('Бренд')->badge()->color('gray'),

                IconColumn::make('is_active')->label('Активен')->boolean()->alignCenter(),
                IconColumn::make('is_indexed')->label('Индекс')->boolean()->alignCenter(),

                TextColumn::make('updated_at')->label('Изменён')->since()->sortable(),
            ])
            ->filters([
                SelectFilter::make('category_id')->label('Категория')->relationship('category', 'name'),
                SelectFilter::make('brand_id')->label('Бренд')->relationship('brand', 'name'),
                TernaryFilter::make('is_active')->label('Активные'),
                TernaryFilter::make('is_indexed')->label('Индексируемые'),
            ])
            ->actions([EditAction::make(), DeleteAction::make()])
            ->bulkActions([BulkActionGroup::make([DeleteBulkAction::make()])])
            ->striped();
    }

    public static function getRelations(): array { return []; }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListSeoFilters::route('/'),
            'create' => Pages\CreateSeoFilter::route('/create'),
            'edit'   => Pages\EditSeoFilter::route('/{record}/edit'),
        ];
    }
}
