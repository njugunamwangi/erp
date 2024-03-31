<?php

namespace App\Filament\Resources;

use App\Filament\Resources\InvoiceResource\Pages;
use App\Filament\Resources\InvoiceResource\RelationManagers;
use App\Filament\Resources\InvoiceResource\Widgets\InvoiceStatsOverview;
use App\Models\Invoice;
use Filament\Forms;
use Filament\Forms\Components\Fieldset;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class InvoiceResource extends Resource
{
    protected static ?string $model = Invoice::class;

    protected static ?string $navigationGroup = 'Customer Relations';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make()
                    ->schema([
                        Grid::make(1)
                            ->schema([
                                Forms\Components\Select::make('user_id')
                                    ->relationship('user', 'name')
                                    ->searchable()
                                    ->preload()
                                    ->required(),
                            ]),
                            Fieldset::make('Invoice Summary')
                                ->schema([
                                    Section::make()
                                        ->schema([
                                            Repeater::make('items')
                                                ->columns(4)
                                                ->live()
                                                ->schema([
                                                    TextInput::make('quantity')
                                                        ->numeric()
                                                        ->required()
                                                        ->live()
                                                        ->default(1),
                                                    TextInput::make('description')
                                                        ->required()
                                                        ->placeholder('Aerial Spraying'),
                                                    TextInput::make('unit_price')
                                                        ->required()
                                                        ->live()
                                                        ->numeric()
                                                        ->default(1000),
                                                    Placeholder::make('sum')
                                                        ->label('Sub Total')
                                                        ->live()
                                                        ->content(function (Get $get) {
                                                            return 'Kes ' . number_format($get('quantity') * $get('unit_price'), 2, '.', ',');
                                                        })
                                                ])
                                                ->afterStateUpdated(function (Get $get, Set $set) {
                                                    self::updateTotals($get, $set);
                                                })
                                                ->addActionLabel('Add Item')
                                                ->columnSpanFull(),
                                        ])->columnSpan(8),
                                    Section::make()
                                        ->schema([
                                            Forms\Components\TextInput::make('subtotal')
                                                ->numeric()
                                                ->readOnly()
                                                ->prefix('Kes')
                                                ->afterStateHydrated(function (Get $get, Set $set) {
                                                    self::updateTotals($get, $set);
                                                }),
                                            Forms\Components\TextInput::make('taxes')
                                                ->suffix('%')
                                                ->required()
                                                ->numeric()
                                                ->default(20)
                                                ->live(true)
                                                ->afterStateUpdated(function (Get $get, Set $set) {
                                                    self::updateTotals($get, $set);
                                                }),
                                            Forms\Components\TextInput::make('total')
                                                ->numeric()
                                                ->readOnly()
                                                ->prefix('Kes')
                                        ])->columnSpan(4),
                                ])->columns(12)
                        ]),
            ]);
    }

    public static function updateTotals(Get $get, Set $set): void
    {
        $items = collect($get('items'));

        $subtotal = 0;

        foreach($items as $item) {
            $aggregate = $item['quantity'] * $item['unit_price'];

            $subtotal += $aggregate;
        }

        $set('subtotal', number_format($subtotal, 2, '.', ''));
        $set('total', number_format($subtotal + ($subtotal * ($get('taxes') / 100)), 2, '.', ''));
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('user.name')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('subtotal')
                    ->numeric()
                    ->money('Kes')
                    ->sortable(),
                Tables\Columns\TextColumn::make('taxes')
                    ->numeric()
                    ->suffix('%')
                    ->sortable(),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(function ($state) {
                        return $state->getColor();
                    })
                    ->icon(function ($state) {
                        return $state->getIcon();
                    }),
                Tables\Columns\TextColumn::make('total')
                    ->numeric()
                    ->money('Kes')
                    ->sortable(),
                Tables\Columns\TextColumn::make('deleted_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\TrashedFilter::make(),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\ForceDeleteBulkAction::make(),
                    Tables\Actions\RestoreBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListInvoices::route('/'),
            'create' => Pages\CreateInvoice::route('/create'),
            'view' => Pages\ViewInvoice::route('/{record}'),
            'edit' => Pages\EditInvoice::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }

    public static function getWidgets(): array
    {
        return [
            InvoiceStatsOverview::class,
        ];
    }
}