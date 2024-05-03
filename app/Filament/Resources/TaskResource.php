<?php

namespace App\Filament\Resources;

use App\Enums\QuoteSeries;
use App\Filament\Resources\TaskResource\Pages;
use App\Mail\RequestFeedbackMail;
use App\Models\Currency;
use App\Models\Equipment;
use App\Models\Expense;
use App\Models\Quote;
use App\Models\Role;
use App\Models\Task;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Components\Fieldset;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Section as ComponentsSection;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Infolists\Components\Actions\Action as ComponentsActionsAction;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;
use Filament\Notifications\Actions\Action;
use Filament\Notifications\Actions\Action as ActionsAction;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Support\Enums\MaxWidth;
use Filament\Tables;
use Filament\Tables\Actions\Action as TablesActionsAction;
use Filament\Tables\Actions\ActionGroup;
use Filament\Tables\Table;
use FilamentTiptapEditor\TiptapEditor;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Mail;

class TaskResource extends Resource
{
    protected static ?string $model = Task::class;

    protected static ?string $navigationGroup = 'Customer Relations';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('assigned_to')
                    ->label('Staff')
                    ->relationship('assignedTo', 'name')
                    ->options(Role::find(Role::STAFF)->users()->get()->pluck('name', 'id'))
                    ->searchable()
                    ->preload()
                    ->required(),
                Forms\Components\Select::make('assigned_for')
                    ->label('Customer')
                    ->relationship('assignedFor', 'name')
                    ->options(Role::find(Role::CUSTOMER)->users()->get()->pluck('name', 'id'))
                    ->searchable()
                    ->preload()
                    ->required(),
                Forms\Components\DatePicker::make('due_date'),
                Select::make('vertical_id')
                    ->relationship('vertical', 'vertical')
                    ->searchable()
                    ->preload()
                    ->live()
                    ->required(),
                TiptapEditor::make('description')
                    ->required()
                    ->extraInputAttributes(['style' => 'min-height: 12rem;'])
                    ->columnSpanFull(),
                Toggle::make('requires_equipment')
                    ->live(),
                Forms\Components\Toggle::make('is_completed'),
                Select::make('equipment')
                    ->visible(fn (Get $get) => $get('requires_equipment'))
                    ->relationship('equipment', 'registration', modifyQueryUsing: fn (Get $get) => Equipment::query()->where('vertical_id', $get('vertical_id')))
                    ->live()
                    ->requiredWith('requires_equipment')
                    ->searchable()
                    ->preload()
                    ->createOptionForm(Equipment::getForm())
                    ->multiple(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort(function ($query) {
                return $query->orderBy('due_date', 'asc')
                    ->orderBy('id', 'desc');
            })
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('assignedBy.name')
                    ->url(fn ($record) => UserResource::getUrl('view', ['record' => $record->assigned_by]))
                    ->icon('heroicon-o-user')
                    ->sortable(),
                Tables\Columns\TextColumn::make('assignedTo.name')
                    ->label('Staff')
                    ->url(fn ($record) => UserResource::getUrl('view', ['record' => $record->assigned_to]))
                    ->icon('heroicon-o-user')
                    ->sortable(),
                Tables\Columns\TextColumn::make('assignedFor.name')
                    ->label('Customer')
                    ->url(fn ($record) => UserResource::getUrl('view', ['record' => $record->assigned_for]))
                    ->icon('heroicon-o-user')
                    ->sortable(),
                Tables\Columns\TextColumn::make('vertical.vertical')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('due_date')
                    ->date()
                    ->sortable(),
                Tables\Columns\IconColumn::make('is_completed')
                    ->label('Completed?')
                    ->boolean(),
                Tables\Columns\IconColumn::make('requires_equipment')
                    ->label('Equipment?')
                    ->boolean(),
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
                    Tables\Actions\ViewAction::make(),
                    Tables\Actions\EditAction::make()
                        ->color('primary'),
                    Tables\Actions\Action::make('Complete')
                        ->color('warning')
                        ->hidden(fn (Task $record) => $record->is_completed)
                        ->icon('heroicon-m-check-badge')
                        ->modalIcon('heroicon-m-check-badge')
                        ->modalHeading('Mark task as completed?')
                        ->modalSubmitActionLabel('Yes')
                        ->modalDescription('Are you sure you want to mark this task as completed?')
                        ->action(function (Task $record) {
                            $record->is_completed = true;
                            $record->save();

                            Mail::to($record->assignedFor->email)->send(new RequestFeedbackMail($record));
                        })
                        ->after(function (Task $record) {
                            $recipients = User::role(Role::ADMIN)->get();

                            foreach ($recipients as $recipient) {
                                Notification::make()
                                    ->title('Task completed')
                                    ->body(auth()->user()->name.' marked task #'.$record->id.' as completed')
                                    ->icon('heroicon-o-check')
                                    ->success()
                                    ->actions([
                                        Action::make('View')
                                            ->url(TaskResource::getUrl('view', ['record' => $record->id]))
                                            ->markAsRead(),
                                    ])
                                    ->sendToDatabase($recipient);
                            }
                        }),
                    TablesActionsAction::make('feedback')
                        ->label('Request Feedback')
                        ->visible(fn ($record) => $record->is_completed && ! $record->feedback)
                        ->color('success')
                        ->icon('heroicon-o-chat-bubble-bottom-center-text')
                        ->action(fn ($record) => Mail::to($record->assignedFor->email)->send(new RequestFeedbackMail($record)))
                        ->after(function ($record) {
                            Notification::make()
                                ->title('Feedback requested for task #'.$record->id)
                                ->send();
                        }),
                    TablesActionsAction::make('expenses')
                        ->icon('heroicon-o-arrow-trending-up')
                        ->color('danger')
                        ->modalWidth(MaxWidth::FiveExtraLarge)
                        ->stickyModalFooter()
                        ->stickyModalHeader()
                        ->modalSubmitActionLabel('Save')
                        ->fillForm(fn (Task $record): array => [
                            'accommodation' => $record->expense?->accommodation,
                            'subsistence' => $record->expense?->subsistence,
                            'fuel' => $record->expense?->fuel,
                            'labor' => $record->expense?->labor,
                            'material' => $record->expense?->material,
                            'misc' => $record->expense?->misc,
                        ])
                        ->form(Expense::getForm())
                        ->action(function (Task $task, array $data) {
                            if ($task->expense) {
                                $task->expense()->update([
                                    'accommodation' => $data['accommodation'],
                                    'subsistence' => $data['subsistence'],
                                    'fuel' => $data['fuel'],
                                    'labor' => $data['labor'],
                                    'material' => $data['material'],
                                    'misc' => $data['misc'],
                                ]);
                            } else {
                                $task->expense()->create([
                                    'accommodation' => $data['accommodation'],
                                    'subsistence' => $data['subsistence'],
                                    'fuel' => $data['fuel'],
                                    'labor' => $data['labor'],
                                    'material' => $data['material'],
                                    'misc' => $data['misc'],
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
                    TablesActionsAction::make('quote')
                        ->label('Generate Quote')
                        ->color('warning')
                        ->modalDescription(fn ($record) => 'Quote for task #'.$record->id)
                        ->visible(fn ($record) => ! $record->quote)
                        ->icon('heroicon-o-banknotes')
                        ->modalSubmitActionLabel('Generate Quote')
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
                                    ComponentsSection::make()
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
                                    ComponentsSection::make()
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
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\ForceDeleteBulkAction::make(),
                    Tables\Actions\RestoreBulkAction::make(),
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
                Section::make('Task Overview')
                    ->headerActions([
                        ComponentsActionsAction::make('completed')
                            ->label('Mark as completed!')
                            ->requiresConfirmation()
                            ->visible(fn ($record) => $record->is_completed === false)
                            ->action(function ($record) {
                                $record->completed();

                                Mail::to($record->assignedFor->email)->send(new RequestFeedbackMail($record));
                            })
                            ->after(function ($record) {
                                $recipients = User::role(Role::ADMIN)->get();

                                foreach ($recipients as $recipient) {
                                    Notification::make()
                                        ->title('Task completed')
                                        ->body(auth()->user()->name.' marked task #'.$record->id.' as completed')
                                        ->icon('heroicon-o-check')
                                        ->success()
                                        ->actions([
                                            ActionsAction::make('View')
                                                ->url(TaskResource::getUrl('view', ['record' => $record->id]))
                                                ->markAsRead(),
                                        ])
                                        ->sendToDatabase($recipient);
                                }
                            }),
                        ComponentsActionsAction::make('feedback')
                            ->label('Request Feedback')
                            ->color('success')
                            ->requiresConfirmation()
                            ->icon('heroicon-o-envelope-open')
                            ->modalIcon('heroicon-o-envelope-open')
                            ->modalSubmitActionLabel('Request Feedback')
                            ->visible(fn ($record) => $record->is_completed === true && ! $record->feedback)
                            ->action(fn ($record) => Mail::to($record->assignedFor->email)->send(new RequestFeedbackMail($record)))
                            ->after(function ($record) {
                                Notification::make()
                                    ->title('Feedback Requested')
                                    ->body('Feedback requested for task #'.$record->id)
                                    ->icon('heroicon-o-check')
                                    ->success()
                                    ->send();
                            }),
                    ])
                    ->schema([
                        TextEntry::make('assignedBy.name')
                            ->label('Assigned By'),
                        TextEntry::make('assignedFor.name')
                            ->label('Customer')
                            ->color('success')
                            ->icon('heroicon-o-user')
                            ->iconColor('success')
                            ->url(fn ($record) => UserResource::getUrl('view', ['record' => $record->assigned_for])),
                        TextEntry::make('assignedTo.name')
                            ->label('Staff')
                            ->color('info')
                            ->iconColor('info')
                            ->icon('heroicon-o-user')
                            ->url(fn ($record) => UserResource::getUrl('view', ['record' => $record->assigned_to])),
                        TextEntry::make('due_date')
                            ->date(),
                        TextEntry::make('description')
                            ->html()
                            ->columnSpanFull(),
                        RepeatableEntry::make('equipment')
                            ->visible(fn ($record) => $record->requires_equipment)
                            ->columnSpanFull()
                            ->schema([
                                TextEntry::make('registration')
                                    ->url(fn ($record) => EquipmentResource::getUrl('view', ['record' => $record->id])),
                            ]),
                    ])->columns(3),
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
            'index' => Pages\ListTasks::route('/'),
            'create' => Pages\CreateTask::route('/create'),
            'view' => Pages\ViewTask::route('/{record}'),
            'edit' => Pages\EditTask::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }
}
