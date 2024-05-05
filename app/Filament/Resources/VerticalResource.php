<?php

namespace App\Filament\Resources;

use App\Filament\Resources\VerticalResource\Pages;
use App\Models\Vertical;
use Filament\Forms\Form;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\Tabs;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class VerticalResource extends Resource
{
    protected static ?string $model = Vertical::class;

    protected static bool $shouldRegisterNavigation = false;

    public static function form(Form $form): Form
    {
        return $form
            ->schema(Vertical::getForm());
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('vertical')
                    ->searchable(),
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
                TextEntry::make('vertical'),
                Tabs::make()
                    ->columnSpanFull()
                    ->tabs([
                        Tabs\Tab::make('Equipment')
                            ->badge(fn($record) => $record->equipment->count())
                            ->schema([
                                RepeatableEntry::make('equipment')
                                    ->schema([
                                        TextEntry::make('registration'),
                                        TextEntry::make('brand.brand'),
                                        TextEntry::make('type'),
                                    ])
                                    ->columns(3)
                            ]),
                        Tabs\Tab::make('Tasks')
                            ->badge(fn($record) => $record->tasks->count())
                            ->schema([
                                RepeatableEntry::make('tasks')
                                    ->schema([
                                        TextEntry::make('id')
                                            ->label('Task')
                                            ->getStateUsing(fn($record) => '#'.$record->id),
                                        TextEntry::make('assignedTo.name')
                                            ->label('Staff'),
                                        TextEntry::make('assignedFor.name')
                                            ->label('Customer'),
                                        IconEntry::make('is_completed'),
                                    ])
                                    ->columns(4)
                            ]),
                        Tabs\Tab::make('Quotes')
                            ->badge(fn($record) => $record->quotes->count())
                            ->schema([
                                RepeatableEntry::make('quotes')
                                    ->schema([
                                        TextEntry::make('serial'),
                                        TextEntry::make('user.name')
                                            ->label('Customer'),
                                        TextEntry::make('currency')
                                            ->getStateUsing(fn($record) => $record->currency->symbol),
                                        TextEntry::make('subtotal')
                                            ->label('Sub-Total'),
                                        TextEntry::make('taxes')
                                            ->suffix('%'),
                                        TextEntry::make('total')
                                    ])
                                    ->columns(6)
                            ]),
                    ])
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
            'index' => Pages\ListVerticals::route('/'),
            'create' => Pages\CreateVertical::route('/create'),
            'view' => Pages\ViewVertical::route('/{record}'),
            'edit' => Pages\EditVertical::route('/{record}/edit'),
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
