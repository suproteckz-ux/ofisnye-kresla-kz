<?php

namespace App\Filament\Resources;

use App\Filament\Resources\BrandResource\Pages;
use App\Models\Brand;
use Filament\Forms\Components\FileUpload;
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
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Support\Str;

class BrandResource extends Resource
{
    protected static ?string $model = Brand::class;

    public static function getNavigationIcon(): string   { return 'heroicon-o-star'; }
    public static function getNavigationGroup(): ?string { return 'Каталог'; }
    public static function getNavigationLabel(): string  { return 'Бренды'; }
    public static function getModelLabel(): string       { return 'Бренд'; }
    public static function getPluralModelLabel(): string { return 'Бренды'; }
    public static function getNavigationSort(): int      { return 3; }

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
                        ->label('Название бренда')
                        ->required()
                        ->maxLength(255)
                        ->live(onBlur: true)
                        ->afterStateUpdated(function (string $state, callable $set, $record) {
                            if (! $record?->slug) {
                                $set('slug', Str::slug($state));
                            }
                        }),

                    TextInput::make('slug')
                        ->label('URL (slug)')
                        ->required()
                        ->unique(Brand::class, 'slug', ignoreRecord: true)
                        ->maxLength(255),

                    Toggle::make('is_active')->label('Активен')->default(true),

                    TextInput::make('sort_order')
                        ->label('Порядок')
                        ->numeric()
                        ->integer()
                        ->default(0),

                    Textarea::make('description')
                        ->label('Описание')
                        ->rows(3)
                        ->nullable()
                        ->columnSpanFull(),
                ]),

            Section::make('Логотип')
                ->schema([
                    FileUpload::make('logo')
                        ->label('Логотип')
                        ->image()
                        ->disk('public')
                        ->directory('brands')
                        ->imagePreviewHeight('120')
                        ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])
                        ->maxSize(2048)
                        ->nullable(),
                ]),

            Section::make('SEO')
                ->collapsed()
                ->schema([
                    TextInput::make('meta_title')->label('Meta Title')->maxLength(255)->nullable(),
                    Textarea::make('meta_description')->label('Meta Description')->maxLength(500)->rows(3)->nullable(),
                    TextInput::make('h1')->label('H1')->maxLength(255)->nullable(),
                    TextInput::make('canonical_url')->label('Canonical URL')->url()->maxLength(500)->nullable(),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                ImageColumn::make('logo')->label('')->disk('public')->size(48),

                TextColumn::make('name')
                    ->label('Бренд')
                    ->searchable()
                    ->sortable()
                    ->description(fn (Brand $r): string => (string) $r->slug),

                IconColumn::make('is_active')->label('Активен')->boolean()->alignCenter(),

                TextColumn::make('sort_order')->label('Порядок')->sortable()->alignCenter(),
            ])
            ->defaultSort('sort_order')
            ->filters([TernaryFilter::make('is_active')->label('Активные')])
            ->actions([EditAction::make(), DeleteAction::make()])
            ->bulkActions([BulkActionGroup::make([DeleteBulkAction::make()])])
            ->striped();
    }

    public static function getRelations(): array { return []; }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListBrands::route('/'),
            'create' => Pages\CreateBrand::route('/create'),
            'edit'   => Pages\EditBrand::route('/{record}/edit'),
        ];
    }
}
