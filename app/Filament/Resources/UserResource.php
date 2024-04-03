<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UserResource\Pages;
use App\Filament\Resources\UserResource\RelationManagers\PipelinesRelationManager;
use App\Models\CustomField;
use App\Models\Lead;
use App\Models\Role;
use App\Models\Stage;
use App\Models\Tag;
use App\Models\Task;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Section as ComponentsSection;
use Filament\Forms\Components\Select;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Infolists\Components\Actions\Action;
use Filament\Infolists\Components\Fieldset;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\Tabs;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\ViewEntry;
use Filament\Infolists\Infolist;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Support\Colors\Color;
use Filament\Tables;
use Filament\Tables\Actions\ActionGroup;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Storage;
use Ysfkaya\FilamentPhoneInput\Forms\PhoneInput;
use Ysfkaya\FilamentPhoneInput\Infolists\PhoneEntry;
use Ysfkaya\FilamentPhoneInput\PhoneInputNumberType;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $navigationGroup = 'User Management';

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
                        ->form([
                            Forms\Components\RichEditor::make('description')
                                ->required(),
                            Forms\Components\Select::make('assigned_to')
                                ->options(Role::find(Role::STAFF)->users()->get()->pluck('name', 'id'))
                                ->preload()
                                ->searchable(),
                            Forms\Components\DatePicker::make('due_date')
                                ->native(false),

                        ])
                        ->action(function (array $data, $record) {
                            $data['assigned_by'] = auth()->id();
                            $data['assigned_for'] = $record->id;

                            Task::create([
                                'assigned_by' => auth()->id(),
                                'assigned_to' => $data['assigned_to'],
                                'assigned_for' => $record->id,
                                'description' => $data['description'],
                                'due_date' => $data['due_date'],
                            ]);

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
                ]),
            ]);
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Section::make('Primary Info')
                    ->columns(3)
                    ->schema([
                        TextEntry::make('name'),
                        TextEntry::make('email'),
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
                    ->hidden(fn ($record) => $record->hasRole(Role::ADMIN))
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
                    ->hidden(fn ($record) => $record->hasRole(Role::ADMIN))
                    ->schema([
                        ViewEntry::make('pipelines')
                            ->label('')
                            ->view('components.filament.pipeline-stage-history-list'),
                    ])
                    ->collapsed(),
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
                                        TextEntry::make('assignedFor.name')
                                            ->hidden(fn ($state) => is_null($state)),
                                        TextEntry::make('due_date')
                                            ->hidden(fn ($state) => is_null($state))
                                            ->date(),
                                    ])
                                    ->columns(),
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
                                        TextEntry::make('assignedFor.name')
                                            ->hidden(fn ($state) => is_null($state)),
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

                                                        Notification::make()
                                                            ->title('Task marked as completed')
                                                            ->success()
                                                            ->send();
                                                    })
                                            ),
                                    ])
                                    ->columns(3),
                            ]),
                    ])
                    ->columnSpanFull(),
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
}
