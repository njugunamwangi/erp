<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UserResource\Pages;
use App\Filament\Resources\UserResource\RelationManagers;
use App\Filament\Resources\UserResource\RelationManagers\PipelinesRelationManager;
use App\Models\Role;
use App\Models\Stage;
use App\Models\Tag;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Section as ComponentsSection;
use Filament\Forms\Components\Select;
use Filament\Forms\Form;
use Filament\Infolists\Components\Fieldset;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\Section;
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
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Storage;
use Ysfkaya\FilamentPhoneInput\Forms\PhoneInput;
use Ysfkaya\FilamentPhoneInput\Infolists\PhoneEntry;
use Ysfkaya\FilamentPhoneInput\PhoneInputNumberType;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $navigationGroup = 'Customer Relations';

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
                            ])
                    ]),
                ComponentsSection::make('Tertiary Details')
                    ->columns(3)
                    ->schema([
                        Select::make('lead_id')
                            ->relationship('lead', 'lead')
                            ->label('Lead Source')
                            ->searchable()
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
                            ->columns()
                    ])
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

                        return $record->name . ' ' . $tagsList;
                    })
                    ->html(),
                Tables\Columns\TextColumn::make('email')
                    ->searchable(),
                Tables\Columns\TextColumn::make('phone')
                    ->searchable(),
                TextColumn::make('lead.lead')
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
                        ->hidden(fn($record) => $record->trashed()),
                    Tables\Actions\EditAction::make()
                        ->hidden(fn($record) => $record->trashed()),
                    Tables\Actions\DeleteAction::make(),
                    Tables\Actions\RestoreAction::make(),
                    Tables\Actions\Action::make('Move to Stage')
                        ->visible(fn(User $user) => !$user->hasRole(Role::ADMIN))
                        ->hidden(fn($record) => $record->trashed())
                        ->icon('heroicon-m-puzzle-piece')
                        ->modalDescription(fn (User $record) => 'Move ' . $record->name . ' to another stage')
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
                                ->label('Notes')
                        ])
                        ->action(function (User $customer, array $data): void {
                            $customer->stage_id = $data['stage_id'];
                            $customer->save();

                            $customer->pipelines()->create([
                                'stage_id' => $data['stage_id'],
                                'notes' => $data['notes'],
                                'user_id' => $customer->id
                            ]);

                            Notification::make()
                                ->title('Pipeline Updated')
                                ->success()
                                ->send();
                        }),
                ])
            ])
            ->recordUrl(function ($record) {
                // If the record is trashed, return null
                if ($record->trashed()) {
                    // Null will disable the row click
                    return null;
                }

                // Otherwise, return the edit page URL
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
                    ->hidden(fn($record) => $record->hasRole(Role::ADMIN))
                    ->schema([
                        TextEntry::make('lead.lead'),
                        TextEntry::make('stage.stage'),
                    ]),
                Section::make('Documents')
                    ->hidden(fn($record) => $record->documents->isEmpty())
                    ->schema([
                        RepeatableEntry::make('documents')
                            ->hiddenLabel()
                            ->schema([
                                TextEntry::make('file_path')
                                    ->label('Document')
                                    ->formatStateUsing(fn() => "Download Document")
                                    ->url(fn($record) => Storage::url($record->file_path), true)
                                    ->badge()
                                    ->color(Color::Blue),
                                TextEntry::make('comments'),
                            ])
                            ->columns()
                    ]),
                Section::make('Pipeline Stage History and Notes')
                    ->hidden(fn($record) => $record->hasRole(Role::ADMIN))
                    ->schema([
                        ViewEntry::make('pipelines')
                            ->label('')
                            ->view('components.filament.pipeline-stage-history-list')
                    ])
                    ->collapsible()
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
