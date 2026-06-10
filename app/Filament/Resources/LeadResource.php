<?php

namespace App\Filament\Resources;

use App\Filament\Resources\LeadResource\Pages;
use App\Models\Lead;
use Filament\Forms\Components\Select;
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
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class LeadResource extends Resource
{
    protected static ?string $model = Lead::class;

    public static function getNavigationIcon(): string   { return 'heroicon-o-phone'; }
    public static function getNavigationGroup(): ?string { return 'CRM'; }
    public static function getNavigationLabel(): string  { return 'Заявки'; }
    public static function getModelLabel(): string       { return 'Заявка'; }
    public static function getPluralModelLabel(): string { return 'Заявки'; }
    public static function getNavigationSort(): int      { return 1; }

    public static function getNavigationBadge(): ?string
    {
        $n = static::getModel()::where('status', 'new')->count();
        return $n > 0 ? (string) $n : null;
    }

    public static function getNavigationBadgeColor(): string|array|null
    {
        return 'warning';
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Данные заявки')
                ->columns(2)
                ->schema([
                    TextInput::make('name')->label('Имя клиента')->required()->maxLength(255),
                    TextInput::make('phone')->label('Телефон')->required()->tel()->maxLength(30),
                    TextInput::make('product_name')->label('Товар')->maxLength(500)->nullable(),

                    Select::make('source')
                        ->label('Источник')
                        ->options([
                            'form'     => 'Форма на сайте',
                            'whatsapp' => 'WhatsApp',
                            'phone'    => 'Звонок',
                            'other'    => 'Другое',
                        ])
                        ->default('form'),

                    Select::make('status')
                        ->label('Статус')
                        ->options([
                            'new'        => 'Новая',
                            'processing' => 'В обработке',
                            'done'       => 'Выполнена',
                            'cancelled'  => 'Отменена',
                        ])
                        ->default('new')
                        ->required(),

                    Textarea::make('comment')->label('Комментарий')->rows(3)->nullable()->columnSpanFull(),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('created_at')
                    ->label('Дата')
                    ->dateTime('d.m.Y H:i')
                    ->sortable(),

                TextColumn::make('name')
                    ->label('Клиент')
                    ->searchable()
                    ->description(fn (Lead $r): string => (string) $r->phone),

                TextColumn::make('phone')
                    ->label('Телефон')
                    ->searchable()
                    ->copyable(),

                TextColumn::make('product_name')
                    ->label('Товар')
                    ->limit(40)
                    ->searchable(),

                TextColumn::make('status')
                    ->label('Статус')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'new'        => 'warning',
                        'processing' => 'primary',
                        'done'       => 'success',
                        'cancelled'  => 'danger',
                        default      => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'new'        => 'Новая',
                        'processing' => 'В обработке',
                        'done'       => 'Выполнена',
                        'cancelled'  => 'Отменена',
                        default      => $state,
                    }),

                TextColumn::make('source')->label('Источник')->badge()->color('gray'),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('status')
                    ->label('Статус')
                    ->options([
                        'new'        => 'Новые',
                        'processing' => 'В обработке',
                        'done'       => 'Выполненные',
                        'cancelled'  => 'Отменённые',
                    ]),
            ])
            ->poll('30s')
            ->actions([EditAction::make(), DeleteAction::make()])
            ->bulkActions([BulkActionGroup::make([DeleteBulkAction::make()])])
            ->striped();
    }

    public static function getRelations(): array { return []; }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListLeads::route('/'),
            'create' => Pages\CreateLead::route('/create'),
            'edit'   => Pages\EditLead::route('/{record}/edit'),
        ];
    }
}
