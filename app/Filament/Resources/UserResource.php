<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UserResource\Pages;
use App\Filament\Resources\UserResource\RelationManagers;
use App\Models\Role;
use App\Models\Stage;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Components\Select;
use Filament\Forms\Form;
use Filament\Infolists\Components\Fieldset;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
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
                Forms\Components\TextInput::make('name')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('email')
                    ->email()
                    ->required()
                    ->maxLength(255),
                PhoneInput::make('phone')
                    ->defaultCountry('KE')
                    ->required()
                    ->displayNumberFormat(PhoneInputNumberType::INTERNATIONAL)
                    ->focusNumberFormat(PhoneInputNumberType::INTERNATIONAL),
                Forms\Components\DateTimePicker::make('email_verified_at'),
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
                Select::make('lead_id')
                    ->relationship('lead', 'lead')
                    ->label('Lead Source')
                    ->searchable()
                    ->preload(),
                Select::make('tags')
                    ->relationship('tags', 'tag')
                    ->multiple()
                    ->searchable()
                    ->preload(),
                Forms\Components\Select::make('pipeline_stage_id')
                    ->relationship('stage', 'stage', function ($query) {
                        $query->orderBy('position', 'asc');
                    })
                    ->searchable()
                    ->preload()
                    ->default(Stage::where('is_default', true)->first()?->id),
                Select::make('roles')
                    ->relationship('roles', 'name')
                    ->searchable()
                    ->preload(),
                Forms\Components\Textarea::make('two_factor_secret')
                    ->columnSpanFull(),
                Forms\Components\Textarea::make('two_factor_recovery_codes')
                    ->columnSpanFull(),
                Forms\Components\DateTimePicker::make('two_factor_confirmed_at'),
                Forms\Components\TextInput::make('current_team_id')
                    ->numeric()
                    ->default('NULL'),
                Forms\Components\TextInput::make('profile_photo_path')
                    ->maxLength(2048)
                    ->default('NULL'),
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
                Tables\Filters\TrashedFilter::make(),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('Move to Stage')
                    ->visible(fn(User $user) => !$user->hasRole(Role::ADMIN))
                    ->icon('heroicon-m-pencil-square')
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
            'index' => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'view' => Pages\ViewUser::route('/{record}'),
            'edit' => Pages\EditUser::route('/{record}/edit'),
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
