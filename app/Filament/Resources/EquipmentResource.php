<?php

namespace App\Filament\Resources;

use App\Filament\Resources\EquipmentResource\Pages;
use App\Models\Equipment;
use Filament\Forms\Form;
use Filament\Infolists\Components\Actions;
use Filament\Infolists\Components\Actions\Action as ActionsAction;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\Tabs;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class EquipmentResource extends Resource
{
    protected static ?string $model = Equipment::class;

    protected static ?string $navigationGroup = 'Asset Management';

    protected static ?string $navigationIcon = 'heroicon-o-eye-dropper';

    public static function form(Form $form): Form
    {
        return $form
            ->schema(Equipment::getForm());
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('registration')
                    ->searchable(),
                Tables\Columns\TextColumn::make('vertical.vertical')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('type'),
                Tables\Columns\TextColumn::make('brand.brand')
                    ->numeric()
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
                Section::make('Overview')
                    ->schema([
                        TextEntry::make('registration'),
                        TextEntry::make('brand.brand')
                            ->url(fn ($record) => BrandResource::getUrl('view', ['record' => $record->brand->id])),
                        TextEntry::make('vertical.vertical')
                            ->url(fn ($record) => VerticalResource::getUrl('view', ['record' => $record->vertical->id])),
                        TextEntry::make('type'),
                    ])
                    ->columns(4),
                Tabs::make()
                    ->columnSpanFull()
                    ->tabs([
                        Tabs\Tab::make('Service History')
                            ->badge(fn ($record) => $record->services->count())
                            ->schema([
                                RepeatableEntry::make('services')
                                    ->hiddenLabel()
                                    ->schema([
                                        TextEntry::make('user.name')
                                            ->label('Technician'),
                                        TextEntry::make('created_at')
                                            ->label('Date')
                                            ->date(),
                                        Actions::make([
                                            ActionsAction::make('view')
                                                ->url(fn ($record) => ServiceResource::getUrl('view', ['record' => $record->id]))
                                                ->link()
                                                ->color('gray')
                                                ->icon('heroicon-o-eye'),
                                        ]),
                                    ])
                                    ->columns(2),
                            ]),
                        Tabs\Tab::make('Work History')
                            ->badge(fn ($record) => $record->services->count())
                            ->schema([
                                RepeatableEntry::make('tasks')
                                    ->hiddenLabel()
                                    ->schema([
                                        TextEntry::make('task')
                                            ->getStateUsing(fn ($record) => '#'.$record->id),
                                        TextEntry::make('due_date')
                                            ->label('Date')
                                            ->date(),
                                        Actions::make([
                                            ActionsAction::make('view')
                                                ->url(fn ($record) => TaskResource::getUrl('view', ['record' => $record->id]))
                                                ->link()
                                                ->color('gray')
                                                ->icon('heroicon-o-eye'),
                                        ]),
                                    ])
                                    ->columns(2),
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
            'index' => Pages\ListEquipment::route('/'),
            'create' => Pages\CreateEquipment::route('/create'),
            'view' => Pages\ViewEquipment::route('/{record}'),
            'edit' => Pages\EditEquipment::route('/{record}/edit'),
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
