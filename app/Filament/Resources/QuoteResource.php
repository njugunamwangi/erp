<?php

namespace App\Filament\Resources;

use App\Enums\InvoiceSeries;
use App\Enums\InvoiceStatus;
use App\Enums\QuoteSeries;
use App\Filament\Resources\QuoteResource\Pages;
use App\Filament\Resources\QuoteResource\Widgets\QuoteOverviewStats;
use App\Mail\SendInvoice;
use App\Models\Currency;
use App\Models\Invoice;
use App\Models\Quote;
use App\Models\Role;
use App\Models\User;
use Brick\Money\Money;
use Filament\Forms;
use Filament\Forms\Components\Fieldset;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
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
use Illuminate\Database\Eloquent\Factories\Relationship;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Mail;

class QuoteResource extends Resource
{
    protected static ?string $model = Quote::class;

    protected static ?string $navigationGroup = 'Customer Relations';

    protected static ?string $recordTitleAttribute = 'serial';

    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make()
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                Forms\Components\Select::make('user_id')
                                    ->relationship('user', 'name')
                                    ->options(Role::find(Role::CUSTOMER)->users()->get()->pluck('name', 'id'))
                                    ->searchable()
                                    ->preload()
                                    ->required()
                                    ->getSearchResultsUsing(fn (string $search): array => User::where('name', 'like', "%{$search}%")->limit(50)->pluck('name', 'id')->toArray())
                                    ->getOptionLabelsUsing(fn (array $values): array => User::whereIn('id', $values)->pluck('name', 'id')->toArray()),
                                Select::make('series')
                                    ->required()
                                    ->enum(QuoteSeries::class)
                                    ->options(QuoteSeries::class)
                                    ->searchable()
                                    ->preload()
                                    ->default(QuoteSeries::IN2QUT->name),
                            ]),
                        Grid::make(2)
                            ->schema([
                                Toggle::make('task')
                                    ->columnSpanFull()
                                    ->label('List Tasks')
                                    ->live(),
                                Select::make('task_id')
                                    ->visible(fn(Get $get) => $get('task') == true)
                                    ->live()
                                    ->relationship('task', 'id', modifyQueryUsing: function (Builder $query, Get $get) {
                                        return $query->where('assigned_for', $get('user_id'))->whereDoesntHave('quote');
                                    })
                                    ->label('Task')
                                    ->searchable()
                                    ->preload(),
                                Select::make('vertical_id')
                                    ->live()
                                    ->visible(fn(Get $get) => $get('task') == false)
                                    ->relationship('vertical', 'vertical')
                                    ->searchable()
                                    ->preload(),
                                Select::make('currency_id')
                                    ->relationship('currency', 'abbr')
                                    ->label('Currency')
                                    ->optionsLimit(40)
                                    ->searchable()
                                    ->createOptionForm(Currency::getForm())
                                    ->editOptionForm(Currency::getForm())
                                    ->live()
                                    ->preload()
                                    ->getSearchResultsUsing(fn (string $search): array => Currency::whereAny([
                                        'name', 'abbr', 'symbol', 'code'], 'like', "%{$search}%")->limit(50)->pluck('abbr', 'id')->toArray())
                                    ->getOptionLabelUsing(fn ($value): ?string => Currency::find($value)?->abbr)
                                    ->loadingMessage('Loading currencies...')
                                    ->searchPrompt('Search currencies by their symbol, abbreviation or country')
                                    ->required(),
                            ]),
                        Fieldset::make('Quote Summary')
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
                                                        return number_format($get('quantity') * $get('unit_price'));
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
                                            ->live()
                                            ->prefix(fn(Get $get) => Currency::where('id', $get('currency_id'))->first()->abbr ?? 'CUR')
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
                                            ->prefix(fn(Get $get) => Currency::where('id', $get('currency_id'))->first()->abbr ?? 'CUR'),
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

        $currency = Currency::where('id', $get('currency_id'))->first();

        $set('subtotal', number_format($subtotal, $currency->precision ?? 0, '.', ''));
        $set('total', number_format($subtotal + ($subtotal * ($get('taxes') / 100)), $currency->precision ?? 0, '.', ''));
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
                    ->view('components.filament.quote-view'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('serial')
                    ->sortable(),
                Tables\Columns\TextColumn::make('user.name')
                    ->url(fn ($record) => UserResource::getUrl('view', ['record' => $record->user_id]))
                    ->color('success')
                    ->icon('heroicon-o-user')
                    ->sortable(),
                Tables\Columns\TextColumn::make('vertical.vertical')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('currency')
                    ->getStateUsing(fn($record) => $record->currency->symbol),
                Tables\Columns\TextColumn::make('subtotal')
                    ->label('Sub-Total')
                    ->sortable(),
                Tables\Columns\TextColumn::make('taxes')
                    ->numeric()
                    ->suffix('%')
                    ->sortable(),
                Tables\Columns\TextColumn::make('total')
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
                        ->label('View Quote'),
                    Tables\Actions\EditAction::make()
                        ->color('primary')
                        ->label('Edit Quote'),
                    Action::make('viewInvoice')
                        ->visible(fn ($record) => $record->invoice)
                        ->icon('heroicon-o-document-check')
                        ->color('warning')
                        ->url(fn ($record) => InvoiceResource::getUrl('view', ['record' => $record->invoice->id])),
                    Action::make('generateInvoice')
                        ->label('Generate Invoice')
                        ->color('warning')
                        ->visible(fn ($record) => ! $record->invoice)
                        ->modalSubmitActionLabel('Generate Invoice')
                        ->icon('heroicon-o-document')
                        ->form([
                            Select::make('series')
                                ->required()
                                ->enum(InvoiceSeries::class)
                                ->options(InvoiceSeries::class)
                                ->searchable()
                                ->preload()
                                ->default(InvoiceSeries::IN2INV->name),
                            Toggle::make('send')
                                ->label('Send Email')
                        ])
                        ->action(function (array $data, $record) {
                            $invoice = Invoice::create([
                                'user_id' => $record->user_id,
                                'quote_id' => $record->id,
                                'status' => InvoiceStatus::Unpaid,
                                'items' => $record->items,
                                'subtotal' => $record->subtotal,
                                'taxes' => $record->taxes,
                                'total' => $record->total,
                                'series' => $data['series'],
                                'serial_number' => $serial_number = Invoice::max('serial_number') + 1,
                                'serial' => $data['series'].'-'.str_pad($serial_number, 5, '0', STR_PAD_LEFT),
                                'currency_id' => $record->currency_id
                            ]);

                            if($data['send'] == true) {

                                $invoice->savePdf();

                                Mail::to($invoice->user->email)->send(new SendInvoice($invoice));
                            }

                            $recipients = User::role(Role::ADMIN)->get();

                            foreach ($recipients as $recipient) {
                                Notification::make()
                                    ->title('Invoice generated')
                                    ->body(auth()->user()->name.' generated an invoice for '.$record->serial)
                                    ->icon('heroicon-o-check-badge')
                                    ->success()
                                    ->actions([
                                        ActionsAction::make('View')
                                            ->url(InvoiceResource::getUrl('view', ['record' => $invoice->id]))
                                            ->markAsRead(),
                                    ])
                                    ->sendToDatabase($recipient);
                            }
                        }),
                    Action::make('pdf')
                        ->label('Download Quote')
                        ->icon('heroicon-o-arrow-down-on-square-stack')
                        ->color('success')
                        ->url(fn (Quote $record) => route('quote.download', $record))
                        ->openUrlInNewTab(),
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
            'index' => Pages\ListQuotes::route('/'),
            'create' => Pages\CreateQuote::route('/create'),
            'view' => Pages\ViewQuote::route('/{record}'),
            'edit' => Pages\EditQuote::route('/{record}/edit'),
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
            QuoteOverviewStats::class,
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
