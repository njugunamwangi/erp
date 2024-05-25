<?php

namespace App\Filament\Clusters\CustomerRelations\Resources\TaskResource\Pages;

use App\Enums\QuoteSeries;
use App\Filament\Clusters\CustomerRelations\Resources\QuoteResource;
use App\Filament\Clusters\CustomerRelations\Resources\TaskResource;
use App\Models\Currency;
use App\Models\Expense;
use App\Models\Note;
use App\Models\Quote;
use App\Models\Role;
use App\Models\Task;
use App\Models\User;
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
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Notifications\Actions\Action as ActionsAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Filament\Support\Enums\MaxWidth;

class ViewTask extends ViewRecord
{
    protected static string $resource = TaskResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ActionGroup::make([
                Actions\EditAction::make()
                    ->icon('heroicon-o-pencil-square'),
                Action::make('expenses')
                    ->icon('heroicon-o-arrow-trending-up')
                    ->color('danger')
                    ->modalWidth(MaxWidth::SevenExtraLarge)
                    ->label('Track Expenses')
                    ->modalDescription(fn ($record) => 'Expenses for task #'.$record->id)
                    ->stickyModalFooter()
                    ->stickyModalHeader()
                    ->modalSubmitActionLabel('Save')
                    ->fillForm(fn (Task $record): array => [
                        'accommodation' => $record->expense?->accommodation,
                        'subsistence' => $record->expense?->subsistence,
                        'equipment' => $record->expense?->equipment,
                        'currency_id' => $record->expense?->currency_id,
                        'fuel' => $record->expense?->fuel,
                        'labor' => $record->expense?->labor,
                        'material' => $record->expense?->material,
                        'misc' => $record->expense?->misc,
                    ])
                    ->form(Expense::getForm())
                    ->action(function (array $data) {
                        $task = $this->getRecord();

                        if ($task->expense) {
                            $task->expense()->update([
                                'accommodation' => $data['accommodation'],
                                'equipment' => $data['equipment'],
                                'total' => $data['total'],
                                'subsistence' => $data['subsistence'],
                                'fuel' => $data['fuel'],
                                'labor' => $data['labor'],
                                'material' => $data['material'],
                                'misc' => $data['misc'],
                                'currency_id' => $data['currency_id'],
                            ]);
                        } else {
                            $task->expense()->create([
                                'accommodation' => $data['accommodation'],
                                'subsistence' => $data['subsistence'],
                                'fuel' => $data['fuel'],
                                'labor' => $data['labor'],
                                'material' => $data['material'],
                                'misc' => $data['misc'],
                                'equipment' => $data['equipment'],
                                'total' => $data['total'],
                                'currency_id' => $data['currency_id'],
                            ]);
                        }
                    })
                    ->after(function (Task $record) {
                        if ($record->expense) {
                            Notification::make()
                                ->title('Expense updated')
                                ->info()
                                ->icon('heroicon-o-check')
                                ->body('Task expenses have been updated successfully')
                                ->send();
                        } else {
                            Notification::make()
                                ->title('Expense created')
                                ->success()
                                ->icon('heroicon-o-check')
                                ->body('Task expenses have been created successfully')
                                ->send();
                        }
                    }),
                Action::make('quote')
                    ->icon('heroicon-o-banknotes')
                    ->color('warning')
                    ->label('Generate Quote')
                    ->modalDescription(fn ($record) => 'Quote for task #'.$record->id)
                    ->modalSubmitActionLabel('Generate Quote')
                    ->visible(fn ($record) => ! $record->quote)
                    ->form([
                        Grid::make(2)
                            ->schema([
                                Select::make('series')
                                    ->required()
                                    ->enum(QuoteSeries::class)
                                    ->options(QuoteSeries::class)
                                    ->searchable()
                                    ->preload()
                                    ->default(QuoteSeries::IN2QUT->name),
                                Select::make('currency_id')
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
                        $quote = $record->quote()->create([
                            'task' => true,
                            'user_id' => $record->assigned_for,
                            'vertical_id' => $record->vertical_id,
                            'subtotal' => $data['subtotal'],
                            'currency_id' => $data['currency_id'],
                            'taxes' => $data['taxes'],
                            'total' => $data['total'],
                            'items' => $data['items'],
                            'series' => $data['series'],
                            'serial_number' => $serial_number = Quote::max('serial_number') + 1,
                            'serial' => $data['series'].'-'.str_pad($serial_number, 5, '0', STR_PAD_LEFT),
                            'notes' => $data['notes'],
                        ]);

                        $recipients = User::role(Role::ADMIN)->get();

                        foreach ($recipients as $recipient) {
                            Notification::make()
                                ->title('Quote generated')
                                ->body(auth()->user()->name.' generated a quote for task #'.$record->id)
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
}
