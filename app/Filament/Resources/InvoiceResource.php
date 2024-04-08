<?php

namespace App\Filament\Resources;

use App\Filament\Resources\InvoiceResource\Pages;
use App\Filament\Resources\InvoiceResource\Widgets\InvoiceStatsOverview;
use App\InvoiceSeries;
use App\InvoiceStatus;
use App\Models\Invoice;
use App\Models\Role;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Components\Fieldset;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Infolists\Components\ViewEntry;
use Filament\Infolists\Infolist;
use Filament\Notifications\Actions\Action as ActionsAction;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\ActionGroup;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class InvoiceResource extends Resource
{
    protected static ?string $model = Invoice::class;
    protected static ?string $navigationGroup = 'Customer Relations';
    protected static ?int $navigationSort = 2;
    protected static ?string $recordTitleAttribute = 'serial';


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
                                    ->required()
                                    ->getSearchResultsUsing(fn (string $search): array => User::where('name', 'like', "%{$search}%")->limit(50)->pluck('name', 'id')->toArray())
                                    ->getOptionLabelsUsing(fn (array $values): array => User::whereIn('id', $values)->pluck('name', 'id')->toArray()),
                            ]),
                        Grid::make(2)
                            ->schema([
                                Select::make('status')
                                    ->enum(InvoiceStatus::class)
                                    ->options(InvoiceStatus::class)
                                    ->required()
                                    ->searchable()
                                    ->preload()
                                    ->default(InvoiceStatus::Unpaid->name),
                                Select::make('series')
                                    ->label('Invoice Series')
                                    ->enum(InvoiceSeries::class)
                                    ->options(InvoiceSeries::class)
                                    ->required()
                                    ->searchable()
                                    ->preload()
                                    ->default(InvoiceSeries::IN2INV->name),
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
                                                        return 'Kes '.number_format($get('quantity') * $get('unit_price'), 2, '.', ',');
                                                    }),
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
                                            ->default(16)
                                            ->live(true)
                                            ->afterStateUpdated(function (Get $get, Set $set) {
                                                self::updateTotals($get, $set);
                                            }),
                                        Forms\Components\TextInput::make('total')
                                            ->numeric()
                                            ->readOnly()
                                            ->prefix('Kes'),
                                    ])->columnSpan(4),
                            ])->columns(12),
                    ]),
            ]);
    }

    public static function updateTotals(Get $get, Set $set): void
    {
        $items = collect($get('items'));

        $subtotal = 0;

        foreach ($items as $item) {
            $aggregate = $item['quantity'] * $item['unit_price'];

            $subtotal += $aggregate;
        }

        $set('subtotal', number_format($subtotal, 2, '.', ''));
        $set('total', number_format($subtotal + ($subtotal * ($get('taxes') / 100)), 2, '.', ''));
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                ViewEntry::make('invoice')
                    ->columnSpanFull()
                    ->viewData([
                        'record' => $infolist->record,
                    ])
                    ->view('components.filament.invoice-view'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('serial')
                    ->sortable(),
                Tables\Columns\TextColumn::make('quote.serial')
                    ->sortable(),
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
                ActionGroup::make([
                    Tables\Actions\ViewAction::make()
                        ->label('View Invoice'),
                    Tables\Actions\EditAction::make()
                        ->color('primary')
                        ->label('Edit Invoice'),
                    Action::make('markPaid')
                        ->label('Mark as Paid')
                        ->visible(fn ($record) => $record->status != InvoiceStatus::Paid)
                        ->color('warning')
                        ->icon('heroicon-o-banknotes')
                        ->requiresConfirmation()
                        ->modalIcon('heroicon-o-banknotes')
                        ->modalDescription(fn ($record) => 'Are you sure you want to mark '.$record->serial.' as paid?')
                        ->modalSubmitActionLabel('Mark as Paid')
                        ->action(function ($record) {
                            $record->status = InvoiceStatus::Paid;
                            $record->save();

                            $recipients = User::role(Role::ADMIN)->get();

                            foreach ($recipients as $recipient) {
                                Notification::make()
                                    ->title('Invoice paid')
                                    ->body(auth()->user()->name.' marked '.$record->serial.' as paid')
                                    ->icon('heroicon-o-banknotes')
                                    ->warning()
                                    ->actions([
                                        ActionsAction::make('View')
                                            ->url(InvoiceResource::getUrl('view', ['record' => $record->id]))
                                            ->markAsRead(),
                                    ])
                                    ->sendToDatabase($recipient);
                            }
                        }),
                    Action::make('pdf')
                        ->label('Download Invoice')
                        ->icon('heroicon-o-arrow-down-on-square-stack')
                        ->color('success')
                        ->url(fn ($record) => route('invoice.download', $record))
                        ->openUrlInNewTab(),
                    Action::make('viewQuote')
                        ->icon('heroicon-o-document-text')
                        ->visible(fn ($record) => $record->quote)
                        ->url(fn ($record) => QuoteResource::getUrl('view', ['record' => $record->quote_id])),
                ]),
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

    public static function getGloballySearchableAttributes(): array
    {
        return ['user.name'];
    }

    public static function getGlobalSearchEloquentQuery(): Builder
    {
        return parent::getGlobalSearchEloquentQuery()->with(['user']);
    }

    public static function getGlobalSearchResultActions(Model $record): array
    {
        return [
            Action::make('view')
                ->url(static::getUrl('view', ['record' => $record]))
                ->link(),
        ];
    }
}
