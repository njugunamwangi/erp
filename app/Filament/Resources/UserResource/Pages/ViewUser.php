<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Enums\InvoiceSeries;
use App\Enums\InvoiceStatus;
use App\Enums\QuoteSeries;
use App\Filament\Clusters\CustomerRelations\Resources\InvoiceResource;
use App\Filament\Clusters\CustomerRelations\Resources\QuoteResource;
use App\Filament\Resources\UserResource;
use App\Filament\Resources\UserResource\Widgets\TasksWidget;
use App\Models\Currency;
use App\Models\Invoice;
use App\Models\Note;
use App\Models\Profile;
use App\Models\Quote;
use App\Models\Role;
use App\Models\Task;
use App\Models\User;
use App\Models\Vertical;
use Filament\Actions;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Forms\Components\Fieldset;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Notifications\Actions\Action as ActionsAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Database\Eloquent\Builder;

class ViewUser extends ViewRecord
{
    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ActionGroup::make([
                Actions\EditAction::make(),
                Action::make('sendSms')
                    ->icon('heroicon-o-chat-bubble-left-right')
                    ->color('warning')
                    ->modalDescription(fn ($record) => 'Draft an sms for '.$record->name)
                    ->modalIcon('heroicon-o-chat-bubble-left-right')
                    ->form([
                        RichEditor::make('message')
                            ->label('SMS')
                            ->required(),
                    ])
                    ->modalSubmitActionLabel('Send SMS')
                    ->action(function ($record, $data) {
                        $message = $data['message'];

                        $record->sendSms($message);
                    })
                    ->after(function ($record) {
                        $recipients = User::role(Role::ADMIN)->get();

                        foreach ($recipients as $recipient) {
                            Notification::make()
                                ->title(auth()->user()->name.' sent an sms')
                                ->body($record->name.' received an sms')
                                ->success()
                                ->icon('heroicon-o-chat-bubble-left-right')
                                ->sendToDatabase($recipient);
                        }
                    }),
                Action::make('quote')
                    ->label('Generate Quote')
                    ->color('success')
                    ->icon('heroicon-o-document-check')
                    ->modalSubmitActionLabel('Generate Quote')
                    ->visible(fn (User $user) => $user->hasRole(Role::CUSTOMER))
                    ->form([
                        Grid::make(2)
                            ->schema([
                                Toggle::make('task')
                                    ->live()
                                    ->columnSpanFull()
                                    ->label('List Tasks')
                                    ->onIcon('heroicon-o-bolt')
                                    ->offIcon('heroicon-o-bolt-slash'),
                                Select::make('task_id')
                                    ->visible(fn (Get $get) => $get('task') == true)
                                    ->label('Task')
                                    ->options(Task::where('assigned_for', '=', $this->record->id)
                                        ->where(fn (Builder $query) => $query->whereDoesntHave('quote'))
                                        ->get()
                                        ->pluck('id'))
                                    ->searchable()
                                    ->preload(),
                                Select::make('vertical_id')
                                    ->live()
                                    ->label('Vertical')
                                    ->visible(fn (Get $get) => $get('task') == false)
                                    ->options(Vertical::all()->pluck('vertical', 'id'))
                                    ->searchable()
                                    ->preload(),
                                Select::make('series')
                                    ->required()
                                    ->enum(QuoteSeries::class)
                                    ->options(QuoteSeries::class)
                                    ->searchable()
                                    ->preload()
                                    ->default(QuoteSeries::IN2QUT->name),
                            ]),
                        Select::make('currency_id')
                            ->label('Currency')
                            ->default(Profile::find(1)->currency_id)
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
                                    ]),
                                Section::make()
                                    ->schema([
                                        TextInput::make('subtotal')
                                            ->numeric()
                                            ->readOnly()
                                            ->prefix(fn (Get $get) => Currency::where('id', $get('currency_id'))->first()->abbr ?? 'CUR')
                                            ->afterStateHydrated(function (Get $get, Set $set) {
                                                self::updateTotals($get, $set);
                                            }),
                                        TextInput::make('taxes')
                                            ->suffix('%')
                                            ->required()
                                            ->numeric()
                                            ->default(16)
                                            ->live(true)
                                            ->afterStateUpdated(function (Get $get, Set $set) {
                                                self::updateTotals($get, $set);
                                            }),
                                        TextInput::make('total')
                                            ->numeric()
                                            ->readOnly()
                                            ->prefix(fn (Get $get) => Currency::where('id', $get('currency_id'))->first()->abbr ?? 'CUR'),
                                    ]),
                            ]),
                        RichEditor::make('notes')
                            ->default(Note::find(1)->quotes)
                            ->disableToolbarButtons([
                                'attachFiles',
                            ]),
                    ])
                    ->action(function (array $data, $record) {
                        $quote = $record->quotes()->create([
                            'task' => $data['task'],
                            'task_id' => empty($data['task_id']) ? null : $data['task_id'],
                            'vertical_id' => empty($data['task_id']) ? $data['vertical_id'] : Task::find($data['task_id'])->vertical_id,
                            'subtotal' => $data['subtotal'],
                            'currency_id' => $data['currency_id'],
                            'taxes' => $data['taxes'],
                            'total' => $data['total'],
                            'items' => $data['items'],
                            'series' => $data['series'],
                            'serial_number' => $serial_number = Quote::max('serial_number') + 1,
                            'notes' => $data['notes'],
                            'serial' => $data['series'].'-'.str_pad($serial_number, 5, '0', STR_PAD_LEFT),
                        ]);

                        $recipients = User::role(Role::ADMIN)->get();

                        foreach ($recipients as $recipient) {
                            Notification::make()
                                ->title('Quote generated')
                                ->body(auth()->user()->name.' generated a quote for '.$record->name)
                                ->icon('heroicon-o-check-badge')
                                ->info()
                                ->actions([
                                    ActionsAction::make('View')
                                        ->url(QuoteResource::getUrl('view', ['record' => $quote->id]))
                                        ->markAsRead(),
                                ])
                                ->sendToDatabase($recipient);
                        }
                    }),
                Action::make('invoice')
                    ->label('Generate Invoice')
                    ->color('primary')
                    ->visible(fn (User $user) => $user->hasRole(Role::CUSTOMER))
                    ->icon('heroicon-o-clipboard-document-check')
                    ->modalSubmitActionLabel('Generate Invoice')
                    ->form([
                        Grid::make(2)
                            ->schema([
                                Select::make('status')
                                    ->enum(InvoiceStatus::class)
                                    ->options(InvoiceStatus::class)
                                    ->searchable()
                                    ->required()
                                    ->default(InvoiceStatus::Unpaid->name),
                                Select::make('series')
                                    ->required()
                                    ->enum(InvoiceSeries::class)
                                    ->options(InvoiceSeries::class)
                                    ->searchable()
                                    ->preload()
                                    ->default(InvoiceSeries::IN2INV->name),
                            ]),
                        Select::make('currency_id')
                            ->label('Currency')
                            ->default(Profile::find(1)->currency_id)
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
                                    ]),
                                Section::make()
                                    ->schema([
                                        TextInput::make('subtotal')
                                            ->numeric()
                                            ->readOnly()
                                            ->prefix(fn (Get $get) => Currency::where('id', $get('currency_id'))->first()->abbr ?? 'CUR')
                                            ->afterStateHydrated(function (Get $get, Set $set) {
                                                self::updateTotals($get, $set);
                                            }),
                                        TextInput::make('taxes')
                                            ->suffix('%')
                                            ->required()
                                            ->numeric()
                                            ->default(16)
                                            ->live(true)
                                            ->afterStateUpdated(function (Get $get, Set $set) {
                                                self::updateTotals($get, $set);
                                            }),
                                        TextInput::make('total')
                                            ->numeric()
                                            ->readOnly()
                                            ->prefix(fn (Get $get) => Currency::where('id', $get('currency_id'))->first()->abbr ?? 'CUR'),
                                    ]),
                            ]),
                        RichEditor::make('notes')
                            ->default(Note::find(1)->quotes)
                            ->disableToolbarButtons([
                                'attachFiles',
                            ]),
                    ])
                    ->action(function (array $data, $record) {
                        $invoice = $record->invoices()->create([
                            'status' => $data['status'],
                            'subtotal' => $data['subtotal'],
                            'currency_id' => $data['currency_id'],
                            'taxes' => $data['taxes'],
                            'total' => $data['total'],
                            'items' => $data['items'],
                            'series' => $data['series'],
                            'serial_number' => $serial_number = Invoice::max('serial_number') + 1,
                            'notes' => $data['notes'],
                            'serial' => $data['series'].'-'.str_pad($serial_number, 5, '0', STR_PAD_LEFT),
                        ]);

                        $recipients = User::role(Role::ADMIN)->get();

                        foreach ($recipients as $recipient) {
                            Notification::make()
                                ->title('Invoice generated')
                                ->body(auth()->user()->name.' generated an invoice for '.$record->name)
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
            ]),
        ];
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

    protected function getHeaderWidgets(): array
    {
        return [
            TasksWidget::class,
        ];
    }
}
