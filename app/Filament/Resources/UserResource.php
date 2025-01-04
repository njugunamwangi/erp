<?php

namespace App\Filament\Resources;

use App\Enums\InvoiceSeries;
use App\Enums\InvoiceStatus;
use App\Filament\Clusters\CustomerRelations\Resources\InvoiceResource;
use App\Filament\Clusters\CustomerRelations\Resources\QuoteResource;
use App\Filament\Clusters\CustomerRelations\Resources\TaskResource;
use App\Filament\Resources\UserResource\Pages;
use App\Filament\Resources\UserResource\RelationManagers\PipelinesRelationManager;
use App\Mail\RequestFeedbackMail;
use App\Mail\SendInvoice;
use App\Models\Account;
use App\Models\Currency;
use App\Models\CustomField;
use App\Models\Equipment;
use App\Models\Invoice;
use App\Models\Lead;
use App\Models\Note;
use App\Models\Role;
use App\Models\Stage;
use App\Models\Tag;
use App\Models\Task;
use App\Models\User;
use App\Models\Vertical;
use Filament\Forms;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Section as ComponentsSection;
use Filament\Forms\Components\Select;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Infolists\Components\Actions;
use Filament\Infolists\Components\Actions\Action;
use Filament\Infolists\Components\Fieldset;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\Tabs;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\ViewEntry;
use Filament\Infolists\Infolist;
use Filament\Notifications\Actions\Action as ActionsAction;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Support\Colors\Color;
use Filament\Support\Enums\Alignment;
use Filament\Tables;
use Filament\Tables\Actions\Action as TablesActionsAction;
use Filament\Tables\Actions\ActionGroup;
use Filament\Tables\Actions\BulkAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Wallo\FilamentSelectify\Components\ToggleButton;
use Ysfkaya\FilamentPhoneInput\Forms\PhoneInput;
use Ysfkaya\FilamentPhoneInput\Infolists\PhoneEntry;
use Ysfkaya\FilamentPhoneInput\PhoneInputNumberType;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $recordTitleAttribute = 'name';

    protected static ?string $navigationGroup = 'Roles and Permissions';

    protected static ?string $navigationIcon = 'heroicon-o-user-group';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                ComponentsSection::make('User Details')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('name')
                                    ->required()
                                    ->maxLength(255),
                                Forms\Components\TextInput::make('email')
                                    ->email()
                                    ->required()
                                    ->maxLength(255),
                            ]),
                        Grid::make(2)
                            ->schema([
                                PhoneInput::make('phone')
                                    ->defaultCountry('KE')
                                    ->required()
                                    ->displayNumberFormat(PhoneInputNumberType::INTERNATIONAL)
                                    ->focusNumberFormat(PhoneInputNumberType::INTERNATIONAL),
                                Select::make('roles')
                                    ->relationship('roles', 'name')
                                    ->searchable()
                                    ->preload(),
                            ]),
                        Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('password')
                                    ->password()
                                    ->required()
                                    ->confirmed()
                                    ->hiddenOn('edit')
                                    ->maxLength(255),
                                Forms\Components\TextInput::make('password_confirmation')
                                    ->label('Confirm Password')
                                    ->password()
                                    ->required()
                                    ->hiddenOn('edit')
                                    ->maxLength(255),
                            ]),
                    ]),
                ComponentsSection::make('Tertiary Details')
                    ->visibleOn('edit')
                    ->columns(3)
                    ->schema([
                        Select::make('lead_id')
                            ->relationship('lead', 'lead')
                            ->label('Lead Source')
                            ->searchable()
                            ->createOptionForm(Lead::getForm())
                            ->editOptionForm(Lead::getForm())
                            ->preload(),
                        Select::make('tags')
                            ->relationship('tags', 'tag')
                            ->multiple()
                            ->searchable()
                            ->createOptionForm(Tag::getForm())
                            ->preload(),
                        Select::make('stage_id')
                            ->relationship('stage', 'stage', function ($query) {
                                $query->orderBy('position', 'asc');
                            })
                            ->searchable()
                            ->createOptionForm(Stage::getForm())
                            ->editOptionForm(Stage::getForm())
                            ->preload()
                            ->default(Stage::where('is_default', true)->first()?->id),
                    ]),
                ComponentsSection::make('Documents')
                    ->visibleOn('edit')
                    ->schema([
                        Forms\Components\Repeater::make('documents')
                            ->relationship('documents')
                            ->hiddenLabel()
                            ->reorderable(false)
                            ->addActionLabel('Add Document')
                            ->schema([
                                Forms\Components\FileUpload::make('file_path')
                                    ->required(),
                                Forms\Components\Textarea::make('comments'),
                            ])
                            ->columns(),
                    ]),
                Forms\Components\Section::make('Additional fields')
                    ->visibleOn('edit')
                    ->schema([
                        Forms\Components\Repeater::make('fields')
                            ->hiddenLabel()
                            ->relationship('customFieldUsers')
                            ->schema([
                                Forms\Components\Select::make('custom_field_id')
                                    ->label('Field Type')
                                    ->options(CustomField::pluck('custom_field', 'id')->toArray())
                                    ->disableOptionWhen(function ($value, $state, Get $get) {
                                        return collect($get('../*.custom_field_id'))
                                            ->reject(fn ($id) => $id === $state)
                                            ->filter()
                                            ->contains($value);
                                    })
                                    ->createOptionForm(CustomField::getForm())
                                    ->editOptionForm(CustomField::getForm())
                                    ->required()
                                    ->searchable()
                                    ->live(),
                                Forms\Components\TextInput::make('value')
                                    ->required(),
                            ])
                            ->addActionLabel('Add another field')
                            ->columns(),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(function ($query) {
                return $query->with('tags');
            })
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->formatStateUsing(function ($record) {
                        $tagsList = view('components.filament.tag-list', ['tags' => $record->tags])->render();

                        return $record->name.' '.$tagsList;
                    })
                    ->html(),
                Tables\Columns\TextColumn::make('email')
                    ->copyable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('phone')
                    ->searchable(),
                TextColumn::make('roles.name')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('deleted_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                // Tables\Filters\TrashedFilter::make(),
            ])
            ->actions([
                ActionGroup::make([
                    Tables\Actions\ViewAction::make()
                        ->hidden(fn ($record) => $record->trashed()),
                    Tables\Actions\EditAction::make()
                        ->hidden(fn ($record) => $record->trashed()),
                    Tables\Actions\DeleteAction::make(),
                    Tables\Actions\RestoreAction::make(),
                    Tables\Actions\Action::make('Move to Stage')
                        ->visible(fn (User $user) => $user->hasRole(Role::CUSTOMER))
                        ->hidden(fn ($record) => $record->trashed())
                        ->icon('heroicon-m-puzzle-piece')
                        ->color('warning')
                        ->modalDescription(fn (User $record) => 'Move '.$record->name.' to another stage')
                        ->modalIcon('heroicon-o-puzzle-piece')
                        ->form([
                            Forms\Components\Select::make('stage_id')
                                ->label('Status')
                                ->searchable()
                                ->options(Stage::pluck('stage', 'id')->toArray())
                                ->default(function (User $record) {
                                    $currentPosition = $record->stage->position;

                                    return Stage::where('position', '>', $currentPosition)->first()?->id;
                                }),
                            Forms\Components\Textarea::make('notes')
                                ->label('Notes'),
                        ])
                        ->action(function (User $customer, array $data, $record) {
                            $customer->stage_id = $data['stage_id'];
                            $customer->save();

                            $customer->pipelines()->create([
                                'stage_id' => $data['stage_id'],
                                'notes' => $data['notes'],
                                'user_id' => $customer->id,
                            ]);
                        })
                        ->after(function (User $record) {
                            $recipients = User::role(Role::ADMIN)->get();

                            foreach ($recipients as $recipient) {
                                Notification::make()
                                    ->title($record->name.' moved to '.$record->stage->stage)
                                    ->body(auth()->user()->name.' moved '.$record->name.' to another stage')
                                    ->icon('heroicon-o-puzzle-piece')
                                    ->success()
                                    ->sendToDatabase($recipient);
                            }
                        }),
                    Tables\Actions\Action::make('Add Task')
                        ->visible(fn (User $user) => $user->hasRole(Role::CUSTOMER))
                        ->icon('heroicon-s-clipboard-document')
                        ->color('info')
                        ->form([
                            Forms\Components\RichEditor::make('description')
                                ->required(),
                            Forms\Components\Select::make('assigned_to')
                                ->label('Staff')
                                ->required()
                                ->options(Role::find(Role::STAFF)->users()->get()->pluck('name', 'id'))
                                ->preload()
                                ->searchable(),
                            Forms\Components\DatePicker::make('due_date')
                                ->native(false),
                            Select::make('vertical_id')
                                ->options(Vertical::all()->pluck('vertical', 'id'))
                                ->searchable()
                                ->preload()
                                ->live()
                                ->required(),
                            Grid::make(2)
                                ->schema([
                                    ToggleButton::make('requires_equipment')
                                        ->visible(fn (Get $get) => $get('vertical_id'))
                                        ->live(),
                                    ToggleButton::make('is_completed'),
                                ]),
                            Select::make('equipment')
                                ->visible(fn (Get $get) => $get('requires_equipment'))
                                ->options(fn (Get $get) => Equipment::query()->where('vertical_id', $get('vertical_id'))->get()->pluck('registration', 'id'))
                                ->live()
                                ->requiredWith('requires_equipment')
                                ->searchable()
                                ->preload()
                                ->multiple(),
                        ])
                        ->action(function (array $data, $record) {
                            $data['assigned_by'] = auth()->id();
                            $data['assigned_for'] = $record->id;

                            $task = Task::create([
                                'assigned_by' => auth()->id(),
                                'assigned_to' => $data['assigned_to'],
                                'assigned_for' => $record->id,
                                'description' => $data['description'],
                                'due_date' => $data['due_date'],
                                'vertical_id' => $data['vertical_id'],
                                'requires_equipment' => $data['requires_equipment'],
                            ]);

                            if ($data['requires_equipment'] && ! empty($data['equipment'])) {
                                $task->equipment()->attach($data['equipment']);
                            }

                            $recipients = User::role(Role::ADMIN)->get();

                            foreach ($recipients as $recipient) {
                                $recipient->notify(
                                    Notification::make()
                                        ->title('Task Assigned')
                                        ->body(auth()->user()->name.' assigned a task for '.$record->name)
                                        ->icon('heroicon-o-puzzle-piece')
                                        ->success()
                                        ->toDatabase()
                                );
                            }
                        }),
                    TablesActionsAction::make('sendSms')
                        ->icon('heroicon-o-chat-bubble-left-right')
                        ->color('success')
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
                ]),
            ])
            ->recordUrl(function ($record) {
                if ($record->trashed()) {
                    return null;
                }

                return Pages\ViewUser::getUrl([$record->id]);
            })
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\ForceDeleteBulkAction::make(),
                    Tables\Actions\RestoreBulkAction::make(),
                    BulkAction::make('sendSms')
                        ->icon('heroicon-o-chat-bubble-left-right')
                        ->color('success')
                        ->modalIcon('heroicon-o-chat-bubble-left-right')
                        ->modalSubmitActionLabel('Send SMS')
                        ->modalDescription(fn (Collection $records) => 'Send an sms to '.$records->count().' members')
                        ->form([
                            RichEditor::make('message')
                                ->label('SMS')
                                ->required(),
                        ])
                        ->action(function (Collection $records, $data) {
                            $message = $data['message'];

                            $records->each->sendSms($message);
                        })
                        ->after(function (Collection $records) {
                            $recipients = User::role(Role::ADMIN)->get();

                            foreach ($recipients as $recipient) {
                                Notification::make()
                                    ->title(auth()->user()->name.' sent bulk smses')
                                    ->body($records->count().' customers received an sms')
                                    ->success()
                                    ->icon('heroicon-o-chat-bubble-left-right')
                                    ->sendToDatabase($recipient);
                            }
                        }),
                ]),
            ]);
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Tabs::make('Tabs')
                    ->columnSpanFull()
                    ->tabs([
                        Tabs\Tab::make('Overview')
                            ->schema([
                                Section::make('Primary Info')
                                    ->columns(3)
                                    ->schema([
                                        TextEntry::make('name'),
                                        TextEntry::make('email')
                                            ->copyable(),
                                        PhoneEntry::make('phone')
                                            ->displayFormat(PhoneInputNumberType::INTERNATIONAL),
                                        Fieldset::make('Tags & Roles')
                                            ->columns(2)
                                            ->schema([
                                                TextEntry::make('roles.name'),
                                                TextEntry::make('tags.tag'),
                                            ]),
                                        Fieldset::make('Verification')
                                            ->columns(2)
                                            ->schema([
                                                TextEntry::make('email_verified_at')
                                                    ->label('Email')
                                                    ->getStateUsing(function ($record) {
                                                        return ($record->email_verified_at == null) ? 'Not Verified' : 'Verified';
                                                    })
                                                    ->badge()
                                                    ->color(function ($state) {
                                                        if ($state === 'Verified') {
                                                            return 'success';
                                                        }

                                                        return 'warning';
                                                    })
                                                    ->icon(function ($state) {
                                                        if ($state === 'Verified') {
                                                            return 'heroicon-o-check-badge';
                                                        }

                                                        return 'heroicon-o-x-circle';
                                                    }),
                                                TextEntry::make('two_factor_confirmed_at')
                                                    ->label('Two Factor Authentication')
                                                    ->getStateUsing(function ($record) {
                                                        return ($record->two_factor_confirmed_at == null) ? 'Not Verified' : 'Verified';
                                                    })
                                                    ->badge()
                                                    ->color(function ($state) {
                                                        if ($state === 'Verified') {
                                                            return 'success';
                                                        }

                                                        return 'warning';
                                                    })
                                                    ->icon(function ($state) {
                                                        if ($state === 'Verified') {
                                                            return 'heroicon-o-check-badge';
                                                        }

                                                        return 'heroicon-o-x-circle';
                                                    }),
                                            ]),
                                    ]),
                                Section::make('Lead & Stage information')
                                    ->visible(fn ($record) => $record->hasRole(Role::CUSTOMER))
                                    ->schema([
                                        TextEntry::make('lead.lead'),
                                        TextEntry::make('stage.stage'),
                                    ])
                                    ->collapsed(),
                                Section::make('Additional fields')
                                    ->hidden(fn ($record) => $record->customFieldUsers->isEmpty())
                                    ->schema(
                                        fn ($record) => $record->customFieldUsers->map(function ($customField) {
                                            return TextEntry::make($customField->customField->custom_field)
                                                ->label($customField->customField->custom_field)
                                                ->default($customField->value);
                                        })->toArray()
                                    )
                                    ->columns()
                                    ->collapsed(),
                                Section::make('Documents')
                                    ->hidden(fn ($record) => $record->documents->isEmpty())
                                    ->schema([
                                        RepeatableEntry::make('documents')
                                            ->hiddenLabel()
                                            ->schema([
                                                TextEntry::make('file_path')
                                                    ->label('Document')
                                                    ->formatStateUsing(fn () => 'Download Document')
                                                    ->url(fn ($record) => Storage::url($record->file_path), true)
                                                    ->badge()
                                                    ->color(Color::Blue),
                                                TextEntry::make('comments'),
                                            ])
                                            ->columns(),
                                    ])
                                    ->collapsed(),
                                Section::make('Pipeline Stage History and Notes')
                                    ->visible(fn ($record) => $record->hasRole(Role::CUSTOMER))
                                    ->schema([
                                        ViewEntry::make('pipelines')
                                            ->label('')
                                            ->view('components.filament.pipeline-stage-history-list'),
                                    ])
                                    ->collapsed(),
                                Section::make('Customer Tasks')
                                    ->visible(fn ($record) => $record->hasRole(Role::CUSTOMER))
                                    ->schema([
                                        Tabs::make('Tasks')
                                            ->tabs([
                                                Tabs\Tab::make('Completed')
                                                    ->badge(fn ($record) => $record->completedTasks->count())
                                                    ->schema([
                                                        RepeatableEntry::make('completedTasks')
                                                            ->hiddenLabel()
                                                            ->schema([
                                                                TextEntry::make('description')
                                                                    ->html()
                                                                    ->columnSpanFull(),
                                                                TextEntry::make('assignedTo.name')
                                                                    ->label('Staff')
                                                                    ->color('primary')
                                                                    ->icon('heroicon-o-user')
                                                                    ->iconColor('primary')
                                                                    ->url(fn ($record) => UserResource::getUrl('view', ['record' => $record->assignedTo->id]))
                                                                    ->hidden(fn ($state) => is_null($state)),
                                                                TextEntry::make('vertical.vertical')
                                                                    ->icon('heroicon-o-adjustments-horizontal'),
                                                                TextEntry::make('due_date')
                                                                    ->hidden(fn ($state) => is_null($state))
                                                                    ->date()
                                                                    ->suffixAction(
                                                                        Action::make('feedback')
                                                                            ->label('Request Feedback')
                                                                            ->color('success')
                                                                            ->icon('heroicon-o-envelope-open')
                                                                            ->requiresConfirmation()
                                                                            ->modalIcon('heroicon-o-envelope-open')
                                                                            ->modalSubmitActionLabel('Request Feedback')
                                                                            ->button()
                                                                            ->visible(fn (Task $record) => ! $record->feedback)
                                                                            ->action(fn (Task $record) => Mail::to($record->assignedFor->email)->send(new RequestFeedbackMail($record)))
                                                                            ->after(function (Task $record) {
                                                                                Notification::make()
                                                                                    ->title('feedback requested')
                                                                                    ->success()
                                                                                    ->body('Feedback requested for task #'.$record->id)
                                                                                    ->send();
                                                                            }),
                                                                    ),
                                                            ])
                                                            ->columns(3),
                                                    ]),
                                                Tabs\Tab::make('Incomplete')
                                                    ->badge(fn ($record) => $record->incompleteTasks->count())
                                                    ->schema([
                                                        RepeatableEntry::make('incompleteTasks')
                                                            ->hiddenLabel()
                                                            ->schema([
                                                                TextEntry::make('description')
                                                                    ->html()
                                                                    ->columnSpanFull(),
                                                                TextEntry::make('assignedTo.name')
                                                                    ->label('Staff')
                                                                    ->color('primary')
                                                                    ->icon('heroicon-o-user')
                                                                    ->iconColor('primary')
                                                                    ->url(fn ($record) => UserResource::getUrl('view', ['record' => $record->assignedTo->id]))
                                                                    ->hidden(fn ($state) => is_null($state)),
                                                                TextEntry::make('vertical.vertical')
                                                                    ->icon('heroicon-o-adjustments-horizontal'),
                                                                TextEntry::make('due_date')
                                                                    ->hidden(fn ($state) => is_null($state))
                                                                    ->date(),
                                                                TextEntry::make('is_completed')
                                                                    ->formatStateUsing(function ($state) {
                                                                        return $state ? 'Yes' : 'No';
                                                                    })
                                                                    ->suffixAction(
                                                                        Action::make('complete')
                                                                            ->button()
                                                                            ->requiresConfirmation()
                                                                            ->modalHeading('Mark task as completed?')
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
                                                                                            ActionsAction::make('View')
                                                                                                ->url(TaskResource::getUrl('view', ['record' => $record->id]))
                                                                                                ->markAsRead(),
                                                                                        ])
                                                                                        ->sendToDatabase($recipient);
                                                                                }
                                                                            })
                                                                    ),
                                                            ])
                                                            ->columns(4),
                                                    ]),
                                            ])
                                            ->columnSpanFull(),
                                    ])
                                    ->collapsed(),
                                Section::make('Staff Tasks')
                                    ->visible(fn ($record) => $record->hasRole(Role::STAFF))
                                    ->schema([
                                        Tabs::make('Tasks')
                                            ->tabs([
                                                Tabs\Tab::make('Completed')
                                                    ->badge(fn ($record) => $record->staffCompletedTasks->count())
                                                    ->schema([
                                                        RepeatableEntry::make('staffCompletedTasks')
                                                            ->hiddenLabel()
                                                            ->schema([
                                                                TextEntry::make('description')
                                                                    ->html()
                                                                    ->columnSpanFull(),
                                                                TextEntry::make('assignedFor.name')
                                                                    ->label('Customer')
                                                                    ->color('success')
                                                                    ->icon('heroicon-o-user')
                                                                    ->iconColor('success')
                                                                    ->url(fn ($record) => UserResource::getUrl('view', ['record' => $record->assignedFor->id]))
                                                                    ->hidden(fn ($state) => is_null($state)),
                                                                TextEntry::make('vertical.vertical')
                                                                    ->icon('heroicon-o-adjustments-horizontal'),
                                                                TextEntry::make('due_date')
                                                                    ->hidden(fn ($state) => is_null($state))
                                                                    ->date()
                                                                    ->suffixAction(
                                                                        Action::make('feedback')
                                                                            ->label('Request Feedback')
                                                                            ->color('success')
                                                                            ->button()
                                                                            ->icon('heroicon-o-envelope-open')
                                                                            ->requiresConfirmation()
                                                                            ->modalIcon('heroicon-o-envelope-open')
                                                                            ->modalSubmitActionLabel('Request Feedback')
                                                                            ->visible(fn (Task $record) => ! $record->feedback)
                                                                            ->action(fn (Task $record) => Mail::to($record->assignedFor->email)->send(new RequestFeedbackMail($record)))
                                                                            ->after(function (Task $record) {
                                                                                Notification::make()
                                                                                    ->title('feedback requested')
                                                                                    ->success()
                                                                                    ->body('Feedback requested for task #'.$record->id)
                                                                                    ->send();
                                                                            }),
                                                                    ),
                                                            ])
                                                            ->columns(3),
                                                    ]),
                                                Tabs\Tab::make('Incomplete')
                                                    ->badge(fn ($record) => $record->staffIncompleteTasks->count())
                                                    ->schema([
                                                        RepeatableEntry::make('staffIncompleteTasks')
                                                            ->hiddenLabel()
                                                            ->schema([
                                                                TextEntry::make('description')
                                                                    ->html()
                                                                    ->columnSpanFull(),
                                                                TextEntry::make('assignedFor.name')
                                                                    ->label('Customer')
                                                                    ->color('success')
                                                                    ->icon('heroicon-o-user')
                                                                    ->iconColor('success')
                                                                    ->url(fn ($record) => UserResource::getUrl('view', ['record' => $record->assignedFor->id]))
                                                                    ->hidden(fn ($state) => is_null($state)),
                                                                TextEntry::make('vertical.vertical')
                                                                    ->icon('heroicon-o-adjustments-horizontal'),
                                                                TextEntry::make('due_date')
                                                                    ->hidden(fn ($state) => is_null($state))
                                                                    ->date(),
                                                                TextEntry::make('is_completed')
                                                                    ->formatStateUsing(function ($state) {
                                                                        return $state ? 'Yes' : 'No';
                                                                    })
                                                                    ->suffixAction(
                                                                        Action::make('complete')
                                                                            ->button()
                                                                            ->requiresConfirmation()
                                                                            ->modalHeading('Mark task as completed?')
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
                                                                                            ActionsAction::make('View')
                                                                                                ->url(TaskResource::getUrl('view', ['record' => $record->id]))
                                                                                                ->markAsRead(),
                                                                                        ])
                                                                                        ->sendToDatabase($recipient);
                                                                                }
                                                                            })
                                                                    ),
                                                            ])
                                                            ->columns(4),
                                                    ]),
                                            ])
                                            ->columnSpanFull(),
                                    ])
                                    ->collapsed(),
                            ]),
                        Tabs\Tab::make('Quotes & Invoices')
                            ->schema([
                                Tabs::make('Tabs')
                                    ->tabs([
                                        Tabs\Tab::make('Quotes')
                                            ->badge(fn ($record) => $record->quotes->count())
                                            ->schema([
                                                RepeatableEntry::make('quotes')
                                                    ->hiddenLabel()
                                                    ->schema([
                                                        TextEntry::make('serial')
                                                            ->label('Serial Number'),
                                                        TextEntry::make('invoice.serial')
                                                            ->label('Invoice Serial No.')
                                                            ->getStateUsing(fn ($record) => $record->invoice ? $record->invoice->serial : '-'),
                                                        TextEntry::make('currency_id')
                                                            ->label('Currency')
                                                            ->getStateUsing(fn ($record) => $record->currency->abbr),
                                                        TextEntry::make('subtotal'),
                                                        TextEntry::make('taxes')
                                                            ->suffix('%'),
                                                        TextEntry::make('total'),
                                                        Actions::make([
                                                            Action::make('Edit Quote')
                                                                ->link()
                                                                ->color('gray')
                                                                ->icon('heroicon-o-pencil-square')
                                                                ->url(fn ($record) => QuoteResource::getUrl('edit', ['record' => $record->id])),
                                                            Action::make('View Quote')
                                                                ->link()
                                                                ->color('success')
                                                                ->icon('heroicon-o-eye')
                                                                ->url(fn ($record) => QuoteResource::getUrl('view', ['record' => $record->id])),
                                                            Action::make('pdf')
                                                                ->link()
                                                                ->label('Download Quote')
                                                                ->icon('heroicon-o-arrow-down-on-square-stack')
                                                                ->color('info')
                                                                ->url(fn ($record) => route('quote.download', $record))
                                                                ->openUrlInNewTab(),
                                                            Action::make('View Invoice')
                                                                ->visible(fn ($record) => $record->invoice)
                                                                ->link()
                                                                ->color('warning')
                                                                ->icon('heroicon-o-eye')
                                                                ->url(fn ($record) => InvoiceResource::getUrl('view', ['record' => $record->invoice->id])),
                                                            Action::make('Generate Invoice')
                                                                ->hidden(fn ($record) => $record->invoice)
                                                                ->link()
                                                                ->color('warning')
                                                                ->icon('heroicon-o-document-check')
                                                                ->modalSubmitActionLabel('Generate Invoice')
                                                                ->modalAlignment(Alignment::Center)
                                                                ->modalDescription(fn ($record) => 'Generate invoice for quote '.$record->serial)
                                                                ->modalIcon('heroicon-o-document-check')
                                                                ->form([
                                                                    Select::make('series')
                                                                        ->required()
                                                                        ->enum(InvoiceSeries::class)
                                                                        ->options(InvoiceSeries::class)
                                                                        ->searchable()
                                                                        ->preload()
                                                                        ->default(InvoiceSeries::IN2INV->name),
                                                                    Select::make('account_id')
                                                                        ->label('Account')
                                                                        ->searchable()
                                                                        ->options(Account::all()->pluck('name', 'id'))
                                                                        ->default(Account::where('enabled', true)->value('id'))
                                                                        ->createOptionForm(Account::getForm())
                                                                        ->getOptionLabelFromRecordUsing(fn (Model $record) => "{$record->name} - {$record->number}")
                                                                        ->preload(),
                                                                    ToggleButton::make('mail')
                                                                        ->label('Mail invoice to customer?'),
                                                                ])
                                                                ->action(function (array $data, $record) {
                                                                    $invoice = Invoice::create([
                                                                        'user_id' => $record->user_id,
                                                                        'currency_id' => $record->currency_id,
                                                                        'quote_id' => $record->id,
                                                                        'items' => $record->items,
                                                                        'subtotal' => $record->subtotal,
                                                                        'taxes' => $record->taxes,
                                                                        'total' => $record->total,
                                                                        'status' => InvoiceStatus::Unpaid,
                                                                        'series' => $data['series'],
                                                                        'mail' => $data['mail'],
                                                                        'account_id' => $data['account_id'],
                                                                        'serial_number' => $serial_number = Invoice::max('serial_number') + 1,
                                                                        'serial' => $data['series'].'-'.str_pad($serial_number, 5, '0', STR_PAD_LEFT),
                                                                        'notes' => Note::find(1)->invoices,
                                                                    ]);

                                                                    if ($data['mail']) {

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
                                                            Action::make('convert')
                                                                ->modalSubmitActionLabel('Convert')
                                                                ->icon('heroicon-o-banknotes')
                                                                ->label('Convert Currency')
                                                                ->color('danger')
                                                                ->link()
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
                                                        ])
                                                            ->columnSpanFull(),
                                                    ])
                                                    ->columns(6),
                                            ]),
                                        Tabs\Tab::make('Invoices')
                                            ->badge(fn ($record) => $record->invoices->count())
                                            ->schema([
                                                RepeatableEntry::make('invoices')
                                                    ->hiddenLabel()
                                                    ->schema([
                                                        TextEntry::make('serial')
                                                            ->label('Serial Number'),
                                                        TextEntry::make('quote.serial')
                                                            ->label('Quote Serial No.')
                                                            ->getStateUsing(fn ($record) => $record->quote ? $record->quote->serial : '-'),
                                                        TextEntry::make('currency_id')
                                                            ->label('Currency')
                                                            ->getStateUsing(fn ($record) => $record->currency->abbr),
                                                        TextEntry::make('subtotal'),
                                                        TextEntry::make('taxes')
                                                            ->suffix('%'),
                                                        TextEntry::make('total'),
                                                        TextEntry::make('status')
                                                            ->badge()
                                                            ->color(function ($state) {
                                                                return $state->getColor();
                                                            })
                                                            ->icon(function ($state) {
                                                                return $state->getIcon();
                                                            }),
                                                        Actions::make([
                                                            Action::make('edit')
                                                                ->label('Edit Invoice')
                                                                ->icon('heroicon-o-pencil-square')
                                                                ->link()
                                                                ->color('gray')
                                                                ->url(fn ($record) => InvoiceResource::getUrl('edit', ['record' => $record->id])),
                                                            Action::make('view')
                                                                ->label('View Invoice')
                                                                ->icon('heroicon-o-eye')
                                                                ->link()
                                                                ->color('success')
                                                                ->url(fn ($record) => InvoiceResource::getUrl('view', ['record' => $record->id])),
                                                            Action::make('pdf')
                                                                ->link()
                                                                ->label('Download Invoice')
                                                                ->icon('heroicon-o-arrow-down-on-square-stack')
                                                                ->color('info')
                                                                ->url(fn ($record) => route('invoice.download', $record))
                                                                ->openUrlInNewTab(),
                                                            Action::make('viewQuote')
                                                                ->visible(fn ($record) => $record->quote)
                                                                ->label('View Quote')
                                                                ->icon('heroicon-o-eye')
                                                                ->link()
                                                                ->color('success')
                                                                ->url(fn ($record) => QuoteResource::getUrl('view', ['record' => $record->quote->id])),
                                                            Action::make('markPaid')
                                                                ->label('Mark as Paid')
                                                                ->link()
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
                                                            Action::make('convert')
                                                                ->modalSubmitActionLabel('Convert')
                                                                ->icon('heroicon-o-banknotes')
                                                                ->label('Convert Currency')
                                                                ->color('danger')
                                                                ->link()
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
                                                        ])->columnSpanFull(),
                                                    ])
                                                    ->columns(7),
                                            ]),
                                    ]),
                            ]),
                    ]),

            ]);
    }

    public static function getRelations(): array
    {
        return [
            // PipelinesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'view' => Pages\ViewUser::route('/{record}'),
            'edit' => Pages\EditUser::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery();
    }

    public static function getGlobalSearchResultActions(Model $record): array
    {
        return [
            Action::make('view')
                ->url(static::getUrl('view', ['record' => $record]))
                ->link(),
        ];
    }

    public static function getGlobalSearchEloquentQuery(): Builder
    {
        return parent::getGlobalSearchEloquentQuery()->with(['quotes', 'invoices']);
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['name', 'quotes.name', 'invoices.name'];
    }
}
