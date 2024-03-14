<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UserResource\Pages;
use App\Filament\Resources\UserResource\RelationManagers;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Infolists\Components\Fieldset;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Ysfkaya\FilamentPhoneInput\Forms\PhoneInput;
use Ysfkaya\FilamentPhoneInput\Infolists\PhoneEntry;
use Ysfkaya\FilamentPhoneInput\PhoneInputNumberType;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

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
                    ->displayNumberFormat(PhoneInputNumberType::E164)
                    ->focusNumberFormat(PhoneInputNumberType::E164),
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
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable(),
                Tables\Columns\TextColumn::make('email')
                    ->searchable(),
                Tables\Columns\TextColumn::make('phone')
                    ->searchable(),
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
                    ->columns(4)
                    ->schema([
                        TextEntry::make('name'),
                        TextEntry::make('email'),
                        PhoneEntry::make('phone')->displayFormat(PhoneInputNumberType::INTERNATIONAL),
                        TextEntry::make('roles.name'),
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
