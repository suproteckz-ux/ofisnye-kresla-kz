<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ProductResource\Pages;
use App\Models\Category;
use App\Models\Product;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\MarkdownEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Notifications\Notification;
use App\Filament\Resources\ProductResource\RelationManagers\ImagesRelationManager;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\SelectColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\TextInputColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class ProductResource extends Resource
{
    protected static ?string $model = Product::class;

    public static function getNavigationIcon(): string   { return 'heroicon-o-cube'; }
    public static function getNavigationGroup(): ?string { return 'Каталог'; }
    public static function getNavigationLabel(): string  { return 'Товары'; }
    public static function getModelLabel(): string       { return 'Товар'; }
    public static function getPluralModelLabel(): string { return 'Товары'; }
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
                    TextInput::make('name')
                        ->label('Название')
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
                        ->unique(Product::class, 'slug', ignoreRecord: true)
                        ->maxLength(255),

                    TextInput::make('sku')
                        ->label('Артикул (SKU)')
                        ->required()
                        ->unique(Product::class, 'sku', ignoreRecord: true)
                        ->maxLength(100),

                    Select::make('category_id')
                        ->label('Категория')
                        ->relationship('category', 'name')
                        ->searchable()
                        ->preload()
                        ->nullable(),

                    Select::make('categories')
                        ->label('Категории')
                        ->relationship('categories', 'name')
                        ->multiple()
                        ->searchable()
                        ->preload()
                        ->helperText('Основная категория выше остается для canonical URL и хлебных крошек.')
                        ->nullable(),

                    Select::make('brand_id')
                        ->label('Бренд')
                        ->relationship('brand', 'name')
                        ->searchable()
                        ->preload()
                        ->nullable(),
                ]),

            Section::make('Цена и наличие')
                ->columns(4)
                ->schema([
                    TextInput::make('price')
                        ->label('Цена (₸)')
                        ->required()
                        ->numeric()
                        ->minValue(0),

                    TextInput::make('old_price')
                        ->label('Старая цена (₸)')
                        ->numeric()
                        ->minValue(0)
                        ->nullable(),

                    TextInput::make('quantity')
                        ->label('Количество')
                        ->numeric()
                        ->integer()
                        ->minValue(0)
                        ->default(0),

                    TextInput::make('sort_order')
                        ->label('Порядок')
                        ->numeric()
                        ->integer()
                        ->default(0),

                    Toggle::make('in_stock')->label('В наличии')->default(true),
                    Toggle::make('is_active')->label('Активен')->default(true),
                    Toggle::make('is_new')->label('Новинка'),
                    Toggle::make('is_hit')->label('Хит'),
                ]),

            Section::make('Изображение')
                ->columns(2)
                ->schema([
                    FileUpload::make('main_image')
                        ->label('Главное фото')
                        ->image()
                        ->disk('public')
                        ->directory('products')
                        ->imagePreviewHeight('200')
                        ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])
                        ->maxSize(5120)
                        ->nullable(),

                    TextInput::make('main_image_alt')
                        ->label('Alt текст')
                        ->maxLength(255)
                        ->nullable(),
                ]),

            // Дополнительные изображения управляются через вкладку «Изображения» (RelationManager)

            Section::make('Описание')
                ->schema([
                    Textarea::make('short_description')
                        ->label('Краткое описание')
                        ->rows(3)
                        ->maxLength(1000)
                        ->nullable(),

                    MarkdownEditor::make('description')
                        ->label('Полное описание')
                        ->nullable()
                        ->columnSpanFull(),
                ]),

            Section::make('Характеристики')
                ->collapsed()
                ->description('Характеристики товара из XML-импорта. Редактируйте вручную при необходимости.')
                ->schema([
                    KeyValue::make('attributes')
                        ->label('')
                        ->keyLabel('Название характеристики')
                        ->valueLabel('Значение')
                        ->addButtonLabel('+ Добавить характеристику')
                        ->reorderable(false)
                        ->nullable()
                        ->columnSpanFull()
                        ->helperText('Данные сохраняются в products.attributes как JSON. Не меняет поведение импорта.')
                        ->dehydrateStateUsing(fn ($state) => array_filter(
                            (array) $state, fn ($v) => $v !== null && $v !== ''
                        )),
                ]),

            Section::make('MarketRadar')
                ->description('Автоматизация цены, остатка, наличия и фото по точному SKU из MarketRadar XML.')
                ->columns(3)
                ->schema([
                    TextInput::make('marketradar_sku_view')
                        ->label('SKU')
                        ->disabled()
                        ->dehydrated(false)
                        ->formatStateUsing(fn (?Product $record): string => (string) ($record?->sku ?? '')),
                    TextInput::make('marketradar_last_status_view')
                        ->label('Последний статус')
                        ->disabled()
                        ->dehydrated(false)
                        ->formatStateUsing(fn (?Product $record): string => (string) ($record?->marketradarSyncLogs()->latest()->value('status') ?? 'нет')),
                    TextInput::make('marketradar_last_sync_view')
                        ->label('Последняя синхронизация')
                        ->disabled()
                        ->dehydrated(false)
                        ->formatStateUsing(fn (?Product $record): string => static::latestMarketRadarDate($record, 'Y-m-d H:i') ?: 'нет'),
                    TextInput::make('marketradar_matched_by_view')
                        ->label('matched_by')
                        ->disabled()
                        ->dehydrated(false)
                        ->formatStateUsing(fn (?Product $record): string => (string) ($record?->marketradarSyncLogs()->latest()->value('matched_by') ?? '')),
                    TextInput::make('marketradar_xml_price_view')
                        ->label('Цена XML')
                        ->disabled()
                        ->dehydrated(false)
                        ->formatStateUsing(fn (?Product $record): string => (string) ($record?->marketradarSyncLogs()->latest()->value('new_price') ?? '')),
                    TextInput::make('marketradar_xml_quantity_view')
                        ->label('Остаток XML')
                        ->disabled()
                        ->dehydrated(false)
                        ->formatStateUsing(fn (?Product $record): string => (string) ($record?->marketradarSyncLogs()->latest()->value('new_quantity') ?? '')),
                    TextInput::make('marketradar_xml_photos_view')
                        ->label('Фото XML')
                        ->disabled()
                        ->dehydrated(false)
                        ->formatStateUsing(fn (?Product $record): string => (string) ($record?->marketradarSyncLogs()->latest()->value('photos_found') ?? '0')),
                    TextInput::make('marketradar_saved_photos_view')
                        ->label('Фото загружено')
                        ->disabled()
                        ->dehydrated(false)
                        ->formatStateUsing(fn (?Product $record): string => (string) ($record?->marketradarSyncLogs()->latest()->value('photos_saved') ?? '0')),
                    TextInput::make('marketradar_error_view')
                        ->label('Ошибка')
                        ->disabled()
                        ->dehydrated(false)
                        ->formatStateUsing(fn (?Product $record): string => Str::limit((string) ($record?->marketradarSyncLogs()->latest()->value('error_message') ?? ''), 180)),
                ])
                ->collapsed(),

            Section::make('SEO')
                ->collapsed()
                ->schema([
                    TextInput::make('meta_title')->label('Meta Title')->maxLength(255)->nullable(),
                    Textarea::make('meta_description')->label('Meta Description')->maxLength(500)->rows(3)->nullable(),
                    TextInput::make('h1')->label('H1')->maxLength(255)->nullable(),
                    Textarea::make('seo_text')->label('SEO-текст')->rows(4)->nullable(),
                    TextInput::make('canonical_url')->label('Canonical URL')->url()->maxLength(500)->nullable(),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([

                // Фото — 50px фиксированная
                ImageColumn::make('main_image')
                    ->label('')
                    ->disk('public')
                    ->size(44)
                    ->width('50px')
                    ->grow(false),

                // Товар: название + SKU — растягивается
                TextColumn::make('name')
                    ->label('Товар')
                    ->searchable()
                    ->sortable()
                    ->limit(60)
                    ->tooltip(fn (Product $r): string => $r->name)
                    ->description(fn (Product $r): string => $r->sku ?? '')
                    ->grow(),

                // Категория — SelectColumn для быстрого изменения
                SelectColumn::make('category_id')
                    ->label('Категория')
                    ->options(fn () => Category::active()
                        ->orderBy('name')
                        ->pluck('name', 'id')
                        ->toArray()
                    )
                    ->width('200px')
                    ->grow(false)
                    ->selectablePlaceholder(false),

                // Цена — inline editing, 110px
                TextInputColumn::make('price')
                    ->label('Цена ₸')
                    ->sortable()
                    ->type('number')
                    ->rules(['numeric', 'min:0'])
                    ->width('110px')
                    ->grow(false),

                // Остаток — inline editing, 70px
                TextInputColumn::make('quantity')
                    ->label('Ост.')
                    ->sortable()
                    ->type('number')
                    ->rules(['integer', 'min:0'])
                    ->width('70px')
                    ->grow(false),

                // В наличии — toggle, 60px
                ToggleColumn::make('in_stock')
                    ->label('Нал.')
                    ->alignCenter()
                    ->width('60px')
                    ->grow(false),

                // Активен — toggle, 60px
                ToggleColumn::make('is_active')
                    ->label('Акт.')
                    ->alignCenter()
                    ->width('60px')
                    ->grow(false),

                // Хит — toggle, 55px
                ToggleColumn::make('is_hit')
                    ->label('Хит')
                    ->alignCenter()
                    ->width('55px')
                    ->grow(false),

                TextColumn::make('images_count')
                    ->label('Фото')
                    ->counts('images')
                    ->alignCenter()
                    ->badge()
                    ->color(fn (?int $state): string => ((int) $state) > 0 ? 'success' : 'gray')
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('marketradar_status')
                    ->label('MarketRadar')
                    ->state(fn (Product $r): string => (string) ($r->marketradarSyncLogs()->latest()->value('status') ?? 'нет'))
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'price_updated', 'stock_updated', 'photos_updated', 'matched' => 'success',
                        'not_found', 'failed' => 'danger',
                        'dry_run' => 'warning',
                        default => 'gray',
                    })
                    ->toggleable(),

                TextColumn::make('marketradar_last_sync')
                    ->label('MR sync')
                    ->state(fn (Product $r): string => static::latestMarketRadarDate($r, null) ?: '—')
                    ->toggleable(isToggledHiddenByDefault: true),

                // Бренд — скрыт по умолчанию
                TextColumn::make('brand.name')
                    ->label('Бренд')
                    ->sortable()
                    ->badge()
                    ->color('primary')
                    ->toggleable(isToggledHiddenByDefault: true),

                // Новинка — скрыта по умолчанию
                ToggleColumn::make('is_new')
                    ->label('Нов.')
                    ->alignCenter()
                    ->toggleable(isToggledHiddenByDefault: true),

                // Изменён — скрыт по умолчанию
                TextColumn::make('updated_at')
                    ->label('Изменён')
                    ->since()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])

            ->filters([
                SelectFilter::make('category_id')
                    ->label('Категория')
                    ->relationship('category', 'name')
                    ->searchable()
                    ->preload(),

                TernaryFilter::make('is_active')->label('Активные'),
                TernaryFilter::make('in_stock')->label('В наличии'),
                TernaryFilter::make('is_hit')->label('Хиты'),
                TernaryFilter::make('is_new')->label('Новинки'),

                TernaryFilter::make('has_image')
                    ->label('Фото')
                    ->nullable()
                    ->trueLabel('Есть фото')
                    ->falseLabel('Без фото')
                    ->queries(
                        true:  fn (Builder $q) => $q->whereNotNull('main_image')->where('main_image', '!=', ''),
                        false: fn (Builder $q) => $q->where(fn ($q) => $q->whereNull('main_image')->orWhere('main_image', '')),
                        blank: fn (Builder $q) => $q,
                    ),
            ])
            ->filtersLayout(FiltersLayout::AboveContentCollapsible)

            ->actions([
                EditAction::make()
                    ->iconButton()
                    ->tooltip('Редактировать'),

                Action::make('view_site')
                    ->label('')
                    ->icon('heroicon-o-arrow-top-right-on-square')
                    ->tooltip('Открыть на сайте')
                    ->color('gray')
                    ->url(function (Product $r): string {
                        $r->loadMissing(['category.parent']);

                        return $r->url;
                    })
                    ->openUrlInNewTab(),

                Action::make('marketradar_check')
                    ->label('')
                    ->icon('heroicon-o-magnifying-glass')
                    ->tooltip('Проверить MarketRadar')
                    ->color('info')
                    ->action(function (Product $record): void {
                        static::runMarketRadarForProduct($record, ['--dry-run' => true, '--no-photos' => true], 'MarketRadar проверен');
                    }),

                Action::make('marketradar_sync')
                    ->label('')
                    ->icon('heroicon-o-arrow-path')
                    ->tooltip('Обновить из MarketRadar')
                    ->color('success')
                    ->requiresConfirmation()
                    ->action(function (Product $record): void {
                        static::runMarketRadarForProduct($record, [], 'MarketRadar обновлен');
                    }),

                Action::make('marketradar_price_stock')
                    ->label('')
                    ->icon('heroicon-o-banknotes')
                    ->tooltip('Обновить цену и остаток')
                    ->color('gray')
                    ->requiresConfirmation()
                    ->action(function (Product $record): void {
                        static::runMarketRadarForProduct($record, ['--no-photos' => true], 'Цена и остаток обновлены');
                    }),

                Action::make('marketradar_photos')
                    ->label('')
                    ->icon('heroicon-o-photo')
                    ->tooltip('Загрузить фото из MarketRadar')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->action(function (Product $record): void {
                        static::runMarketRadarForProduct($record, ['--photos' => true, '--no-prices' => true, '--no-stock' => true, '--only-missing-photos' => true], 'Фото MarketRadar обработаны');
                    }),

                Action::make('kaspi_photos')
                    ->visible(false)
                    ->label('')
                    ->icon('heroicon-o-photo')
                    ->tooltip('Загрузить фото с Kaspi')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->modalHeading('Загрузить фото с Kaspi')
                    ->modalDescription('Фото будут только добавлены в галерею товара. Цена, остатки, название, описание, SEO и категории не меняются.')
                    ->action(function (Product $record): void {
                        Artisan::call('kaspi:photos', [
                            '--id' => $record->id,
                            '--limit' => 1,
                            '--append-only' => true,
                        ]);

                        $output = trim(Artisan::output());

                        Notification::make()
                            ->title('Импорт фото Kaspi завершён')
                            ->body(Str::limit($output ?: 'Команда выполнена.', 500))
                            ->success()
                            ->send();
                    }),

                DeleteAction::make()
                    ->iconButton()
                    ->tooltip('Удалить'),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    BulkAction::make('marketradar_bulk_check')
                        ->label('Проверить MarketRadar')
                        ->requiresConfirmation()
                        ->action(fn (Collection $records) => static::runMarketRadarBulk($records, ['--dry-run' => true, '--no-photos' => true], 'MarketRadar проверен')),
                    BulkAction::make('marketradar_bulk_price_stock')
                        ->label('Обновить цену и остаток')
                        ->requiresConfirmation()
                        ->action(fn (Collection $records) => static::runMarketRadarBulk($records, ['--no-photos' => true], 'Цена и остаток обновлены')),
                    BulkAction::make('marketradar_bulk_photos')
                        ->label('Загрузить фото')
                        ->requiresConfirmation()
                        ->action(fn (Collection $records) => static::runMarketRadarBulk($records, ['--photos' => true, '--no-prices' => true, '--no-stock' => true, '--only-missing-photos' => true], 'Фото MarketRadar обработаны')),
                    BulkAction::make('marketradar_bulk_all')
                        ->label('Обновить всё')
                        ->requiresConfirmation()
                        ->action(fn (Collection $records) => static::runMarketRadarBulk($records, [], 'MarketRadar обновлен')),
                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('updated_at', 'desc')
            ->paginated([25, 50, 100])
            ->striped()
            ->persistFiltersInSession()
            ->persistSortInSession()
            ->persistSearchInSession();
    }

    public static function getRelations(): array
    {
        return [
            ImagesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListProducts::route('/'),
            'create' => Pages\CreateProduct::route('/create'),
            'edit'   => Pages\EditProduct::route('/{record}/edit'),
        ];
    }

    private static function runMarketRadarForProduct(Product $record, array $options, string $title): void
    {
        Artisan::call('marketradar:sync', [
            '--sku' => $record->sku,
            '--limit' => 1,
            ...$options,
        ]);

        static::notify($title, Artisan::output());
    }

    private static function runMarketRadarBulk(Collection $records, array $options, string $title): void
    {
        $processed = 0;
        $output = [];

        foreach ($records->take(20) as $record) {
            Artisan::call('marketradar:sync', [
                '--sku' => $record->sku,
                '--limit' => 1,
                ...$options,
            ]);

            $output[] = trim(Artisan::output());
            $processed++;
        }

        static::notify($title . " ({$processed})", implode("\n", array_slice($output, -3)));
    }

    private static function notify(string $title, string $body): void
    {
        Notification::make()
            ->title($title)
            ->body(Str::limit(trim($body) ?: 'Команда выполнена.', 700))
            ->success()
            ->send();
    }

    private static function latestMarketRadarDate(?Product $record, ?string $format): ?string
    {
        $value = $record?->marketradarSyncLogs()->latest()->value('created_at');
        if (! $value) {
            return null;
        }

        try {
            $date = $value instanceof \DateTimeInterface ? $value : \Illuminate\Support\Carbon::parse((string) $value);
        } catch (\Throwable) {
            return null;
        }

        return $format ? $date->format($format) : $date->diffForHumans();
    }
}
