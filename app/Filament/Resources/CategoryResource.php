<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CategoryResource\Pages;
use App\Models\Category;
use Filament\Forms\Components\FileUpload;
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
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Support\Str;

class CategoryResource extends Resource
{
    protected static ?string $model = Category::class;

    public static function getNavigationIcon(): string   { return 'heroicon-o-tag'; }
    public static function getNavigationGroup(): ?string { return 'Каталог'; }
    public static function getNavigationLabel(): string  { return 'Категории'; }
    public static function getModelLabel(): string       { return 'Категория'; }
    public static function getPluralModelLabel(): string { return 'Категории'; }
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
                        ->label('Название')
                        ->required()
                        ->maxLength(255)
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
                        ->unique(Category::class, 'slug', ignoreRecord: true)
                        ->maxLength(255),

                    Select::make('parent_id')
                        ->label('Родительская категория')
                        ->relationship('parent', 'name')
                        ->searchable()
                        ->preload()
                        ->nullable(),

                    Toggle::make('is_active')
                        ->label('Активна')
                        ->default(true),

                    TextInput::make('sort_order')
                        ->label('Порядок сортировки')
                        ->numeric()
                        ->integer()
                        ->default(0),
                ]),

            Section::make('Изображение')
                ->schema([
                    FileUpload::make('image')
                        ->label('Изображение')
                        ->image()
                        ->disk('public')
                        ->directory('categories')
                        ->imagePreviewHeight('180')
                        ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])
                        ->maxSize(3072)
                        ->nullable(),
                ]),

            Section::make('SEO')
                ->collapsed()
                ->schema([
                    TextInput::make('meta_title')->label('Meta Title')->maxLength(255)->nullable(),
                    Textarea::make('meta_description')->label('Meta Description')->maxLength(500)->rows(3)->nullable(),
                    TextInput::make('h1')->label('H1')->maxLength(255)->nullable(),
                    Textarea::make('seo_text_top')->label('SEO-текст (вверху)')->rows(4)->nullable(),
                    Textarea::make('seo_text_bottom')->label('SEO-текст (внизу)')->rows(4)->nullable(),
                    TextInput::make('canonical_url')->label('Canonical URL')->url()->maxLength(500)->nullable(),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                ImageColumn::make('image')->label('')->disk('public')->size(48),

                TextColumn::make('name')
                    ->label('Название')
                    ->searchable()
                    ->sortable()
                    ->description(fn (Category $r): string => (string) $r->slug),

                TextColumn::make('parent.name')
                    ->label('Родитель')
                    ->badge()
                    ->color('gray'),

                IconColumn::make('is_active')->label('Активна')->boolean()->alignCenter(),

                TextColumn::make('sort_order')
                    ->label('Порядок')
                    ->sortable()
                    ->alignCenter(),
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
            'index'  => Pages\ListCategories::route('/'),
            'create' => Pages\CreateCategory::route('/create'),
            'edit'   => Pages\EditCategory::route('/{record}/edit'),
        ];
    }
}
