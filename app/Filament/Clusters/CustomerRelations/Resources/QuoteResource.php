<?php

namespace App\Filament\Clusters\CustomerRelations\Resources;

use App\Enums\InvoiceSeries;
use App\Enums\InvoiceStatus;
use App\Enums\QuoteSeries;
use App\Filament\Clusters\Banking\Resources\CurrencyResource;
use App\Filament\Clusters\CustomerRelations;
use App\Filament\Clusters\CustomerRelations\Resources\QuoteResource\Pages;
use App\Filament\Clusters\CustomerRelations\Resources\QuoteResource\Widgets\QuoteOverviewStats;
use App\Filament\Resources\UserResource;
use App\Filament\Resources\VerticalResource;
use App\Mail\SendInvoice;
use App\Models\Account;
use App\Models\Currency;
use App\Models\Invoice;
use App\Models\Note;
use App\Models\Profile;
use App\Models\Quote;
use App\Models\Role;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Components\Fieldset;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
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
use Filament\Support\Enums\Alignment;
use Filament\Support\Enums\MaxWidth;
use Filament\Tables;
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\ActionGroup;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Wallo\FilamentSelectify\Components\ToggleButton;

class QuoteResource extends Resource
{
    protected static ?string $model = Quote::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-check';

    protected static ?int $navigationSort = 4;

    protected static ?string $cluster = CustomerRelations::class;

    protected static ?string $recordTitleAttribute = 'serial';

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::count();
    }

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
                                    ->live()
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
                                    ->onIcon('heroicon-o-bolt')
                                    ->offIcon('heroicon-o-bolt-slash')
                                    ->visible(fn (Get $get) => $get('user_id'))
                                    ->live(),
                                Select::make('task_id')
                                    ->visible(fn (Get $get) => $get('task') == true)
                                    ->live()
                                    ->relationship('task', 'id', modifyQueryUsing: function (Builder $query, Get $get) {
                                        return $query->where('assigned_for', $get('user_id'))->whereDoesntHave('quote');
                                    })
                                    ->label('Task')
                                    ->searchable()
                                    ->preload(),
                                Select::make('vertical_id')
                                    ->live()
                                    ->visible(fn (Get $get) => $get('task') == false)
                                    ->relationship('vertical', 'vertical')
                                    ->searchable()
                                    ->preload(),
                                Select::make('currency_id')
                                    ->relationship('currency', 'abbr')
                                    ->label('Currency')
                                    ->default(Profile::find(1)->currency_id)
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
                        Grid::make(2)
                            ->schema([
                                ToggleButton::make('mail')
                                    ->label('Send Email to Customer?')
                                    ->default(true),
                                Select::make('account_id')
                                    ->label('Account')
                                    ->relationship('account', 'name')
                                    ->searchable()
                                    ->default(Account::where('enabled', true)->value('id'))
                                    ->createOptionForm(Account::getForm())
                                    ->getOptionLabelFromRecordUsing(fn (Model $record) => "{$record->name} - {$record->number}")
                                    ->preload(),
                            ]),
                        Fieldset::make('Quote Summary')
                            ->schema([
                                Section::make()
                                    ->schema([
                                        Repeater::make('items')
                                            ->columns(4)
                                            ->live()
                                            ->schema([
                                                Textarea::make('description')
                                                    ->required()
                                                    ->placeholder('Aerial Spraying'),
                                                TextInput::make('quantity')
                                                    ->numeric()
                                                    ->required()
                                                    ->live()
                                                    ->default(1),
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
                                            ->prefix(fn (Get $get) => Currency::where('id', $get('currency_id'))->first()->abbr ?? 'CUR')
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
                                            ->prefix(fn (Get $get) => Currency::where('id', $get('currency_id'))->first()->abbr ?? 'CUR'),
                                    ])->columnSpan(4),
                            ])->columns(12),
                        RichEditor::make('notes')
                            ->default(Note::find(1)?->quotes)
                            ->required()
                            ->disableToolbarButtons([
                                'attachFiles',
                            ]),
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
                    ->label('Customer')
                    ->icon('heroicon-o-user')
                    ->sortable(),
                Tables\Columns\TextColumn::make('vertical.vertical')
                    ->url(fn ($record) => VerticalResource::getUrl('view', ['record' => $record->vertical_id]))
                    ->sortable(),
                Tables\Columns\TextColumn::make('currency')
                    ->getStateUsing(fn ($record) => $record->currency->abbr)
                    ->description(fn ($record) => $record->currency->name)
                    ->url(fn ($record) => CurrencyResource::getUrl('view', ['record' => $record->currency_id])),
                Tables\Columns\TextColumn::make('subtotal')
                    ->getStateUsing(fn ($record) => $record->subtotal->formatTo($record->currency->locale))
                    ->label('Sub-Total')
                    ->sortable(),
                Tables\Columns\TextColumn::make('taxes')
                    ->numeric()
                    ->suffix('%')
                    ->sortable(),
                Tables\Columns\TextColumn::make('total')
                    ->getStateUsing(fn ($record) => $record->total->formatTo($record->currency->locale))
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
                        ->modalWidth(MaxWidth::SevenExtraLarge)
                        ->fillForm(fn ($record): array => [
                            'items' => $record?->items,
                            'taxes' => $record?->taxes,
                        ])
                        ->form(fn ($record) => [
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
                                                ->prefix($record->currency->abbr)
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
                                                ->prefix($record->currency->abbr),
                                        ])->columnSpan(4),
                                ])->columns(12),
                            Select::make('series')
                                ->default(InvoiceSeries::DEFAULT)
                                ->required()
                                ->enum(InvoiceSeries::class)
                                ->options(InvoiceSeries::class)
                                ->searchable()
                                ->preload(),
                            ToggleButton::make('send')
                                ->label('Send Email to customer?'),
                        ])
                        ->action(function (array $data, $record) {
                            $invoice = $record->invoice()->create([
                                'user_id' => $record->user_id,
                                'quote_id' => $record->id,
                                'status' => InvoiceStatus::Unpaid,
                                'items' => $record->items,
                                'subtotal' => $data['subtotal'],
                                'taxes' => $data['taxes'],
                                'total' => $data['total'],
                                'series' => $data['series'],
                                'serial_number' => $serial_number = Invoice::max('serial_number') + 1,
                                'serial' => $data['series'].'-'.str_pad($serial_number, 5, '0', STR_PAD_LEFT),
                                'currency_id' => $record->currency_id,
                                'notes' => Note::find(1)->invoices,
                                'mail' => $data['send'],
                            ]);

                            if ($data['send'] == true) {

                                $invoice->savePdf();

                                Mail::to($invoice->user->email)->send(new SendInvoice($invoice));

                                $name = 'invoice_'.$invoice->series->name.'_'.str_pad($invoice->serial_number, 5, '0', STR_PAD_LEFT).'.pdf';

                                Storage::disk('invoices')->delete($name);
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
                    Action::make('convert')
                        ->modalSubmitActionLabel('Convert')
                        ->icon('heroicon-o-banknotes')
                        ->label('Convert Currency')
                        ->color('danger')
                        ->modalAlignment(Alignment::Center)
                        ->modalDescription(fn ($record) => 'Converting currency for '.$record->serial.' from '.$record->currency->abbr)
                        ->modalIcon('heroicon-o-banknotes')
                        ->form([
                            Select::make('currency_id')
                                ->options(Currency::all()->pluck('abbr', 'id'))
                                ->label('Currency')
                                ->optionsLimit(40)
                                ->searchable()
                                ->createOptionForm(Currency::getForm())
                                ->live()
                                ->preload()
                                ->getSearchResultsUsing(fn (string $search): array => Currency::whereAny([
                                    'name', 'abbr', 'symbol', 'code'], 'like', "%{$search}%")->limit(50)->pluck('abbr', 'id')->toArray())
                                ->getOptionLabelUsing(fn ($value): ?string => Currency::find($value)?->abbr)
                                ->loadingMessage('Loading currencies...')
                                ->searchPrompt('Search currencies by their symbol, abbreviation or country')
                                ->required(),
                        ])
                        ->action(function ($record, array $data) {
                            $record->convertCurrency($data);

                            // Notification
                            $recipients = User::role(Role::ADMIN)->get();

                            foreach ($recipients as $recipient) {
                                Notification::make()
                                    ->title('Currency converted')
                                    ->body(auth()->user()->name.' converted currency for '.$record->serial)
                                    ->icon('heroicon-o-banknotes')
                                    ->danger()
                                    ->actions([
                                        ActionsAction::make('View')
                                            ->url(QuoteResource::getUrl('view', ['record' => $record->id]))
                                            ->markAsRead(),
                                    ])
                                    ->sendToDatabase($recipient);
                            }
                        }),
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
