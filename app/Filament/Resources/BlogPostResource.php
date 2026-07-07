<?php

namespace App\Filament\Resources;

use App\Filament\Resources\BlogPostResource\Pages;
use App\Models\BlogPost;
use App\Models\Product;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\MarkdownEditor;
use Filament\Forms\Components\Repeater;
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

class BlogPostResource extends Resource
{
    protected static ?string $model = BlogPost::class;

    public static function getNavigationIcon(): string   { return 'heroicon-o-document-text'; }
    public static function getNavigationGroup(): ?string { return 'Контент'; }
    public static function getNavigationLabel(): string  { return 'Блог'; }
    public static function getModelLabel(): string       { return 'Статья'; }
    public static function getPluralModelLabel(): string { return 'Статьи блога'; }
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
                        ->label('Заголовок')
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
                        ->unique(BlogPost::class, 'slug', ignoreRecord: true)
                        ->maxLength(255),

                    DateTimePicker::make('published_at')
                        ->label('Дата публикации')
                        ->nullable()
                        ->displayFormat('d.m.Y H:i')
                        ->timezone('Asia/Almaty'),

                    Toggle::make('is_active')->label('Опубликована')->default(false),
                ]),

            Section::make('Обложка')
                ->columns(2)
                ->schema([
                    FileUpload::make('cover_image')
                        ->label('Обложка')
                        ->image()
                        ->disk('public')
                        ->directory('blog')
                        ->imagePreviewHeight('200')
                        ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])
                        ->maxSize(5120)
                        ->nullable(),

                    TextInput::make('cover_image_alt')
                        ->label('Alt изображения')
                        ->maxLength(255)
                        ->nullable(),
                ]),

            Section::make('Контент')
                ->schema([
                    MarkdownEditor::make('content')
                        ->label('Текст статьи')
                        ->nullable()
                        ->columnSpanFull(),
                ]),

            Section::make('FAQ')
                ->collapsed()
                ->schema([
                    Repeater::make('faq')
                        ->label('')
                        ->schema([
                            TextInput::make('question')->label('Вопрос')->required(),
                            Textarea::make('answer')->label('Ответ')->required()->rows(3),
                        ])
                        ->addActionLabel('Добавить вопрос')
                        ->defaultItems(0)
                        ->reorderable()
                        ->columnSpanFull(),
                ]),

            Section::make('Связанные товары')
                ->collapsed()
                ->schema([
                    Select::make('products')
                        ->label('Товары из статьи')
                        ->relationship('products', 'name')
                        ->multiple()
                        ->searchable()
                        ->preload()
                        ->getOptionLabelFromRecordUsing(fn (Product $record): string => trim(($record->sku ? $record->sku.' — ' : '').$record->name))
                        ->helperText('Используется существующая связь blog_post_product. Если товары не выбраны, на сайте покажутся популярные товары.')
                        ->columnSpanFull(),
                ]),

            Section::make('SEO')
                ->collapsed()
                ->schema([
                    TextInput::make('h1')->label('H1')->maxLength(255)->nullable(),
                    TextInput::make('meta_title')->label('Meta Title')->maxLength(255)->nullable(),
                    Textarea::make('meta_description')->label('Meta Description')->maxLength(500)->rows(3)->nullable(),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                ImageColumn::make('cover_image')->label('')->disk('public')->size(48),

                TextColumn::make('title')
                    ->label('Заголовок')
                    ->searchable()
                    ->sortable()
                    ->limit(60)
                    ->description(fn (BlogPost $r): string => (string) $r->slug),

                IconColumn::make('is_active')->label('Опубликована')->boolean()->alignCenter(),

                TextColumn::make('published_at')
                    ->label('Дата')
                    ->dateTime('d.m.Y')
                    ->sortable(),

                TextColumn::make('updated_at')->label('Изменена')->since()->sortable(),
            ])
            ->defaultSort('published_at', 'desc')
            ->filters([TernaryFilter::make('is_active')->label('Опубликованные')])
            ->actions([EditAction::make(), DeleteAction::make()])
            ->bulkActions([BulkActionGroup::make([DeleteBulkAction::make()])])
            ->striped();
    }

    public static function getRelations(): array { return []; }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListBlogPosts::route('/'),
            'create' => Pages\CreateBlogPost::route('/create'),
            'edit'   => Pages\EditBlogPost::route('/{record}/edit'),
        ];
    }
}
