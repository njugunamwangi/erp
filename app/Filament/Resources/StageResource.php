<?php

namespace App\Filament\Resources;

use App\Filament\Resources\StageResource\Pages;
use App\Filament\Resources\StageResource\RelationManagers;
use App\Models\Stage;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Actions\ActionGroup;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class StageResource extends Resource
{
    protected static ?string $model = Stage::class;

    protected static ?string $navigationGroup = 'Customer Relations';

    public static function form(Form $form): Form
    {
        return $form
            ->schema(Stage::getForm());
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('stage')
                    ->searchable(),
                Tables\Columns\IconColumn::make('is_default')
                    ->boolean(),
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
            ->defaultSort('position')
            ->reorderable('position')
            ->filters([
                Tables\Filters\TrashedFilter::make(),
            ])
            ->actions([
                ActionGroup::make([
                    Tables\Actions\Action::make('Set Default')
                        ->icon('heroicon-o-star')
                        ->hidden(fn($record) => $record->is_default)
                        ->requiresConfirmation(function (Tables\Actions\Action $action, $record) {
                            $action->modalDescription('Are you sure you want to set this as the default pipeline stage?');
                            $action->modalHeading('Set "' . $record->stage . '" as Default');

                            return $action;
                        })
                        ->action(function (Stage $record) {
                            Stage::where('is_default', true)->update(['is_default' => false]);

                            $record->is_default = true;
                            $record->save();
                        }),
                    Tables\Actions\ViewAction::make(),
                    Tables\Actions\EditAction::make(),
                    Tables\Actions\DeleteAction::make()
                        ->action(function ($data, $record) {
                            if ($record->users()->count() > 0) {
                                Notification::make()
                                    ->danger()
                                    ->title('Pipeline Stage is in use')
                                    ->body('Pipeline Stage is in use by customers.')
                                    ->send();

                                return;
                            }

                            Notification::make()
                                ->success()
                                ->title('Pipeline Stage deleted')
                                ->body('Pipeline Stage has been deleted.')
                                ->send();

                            $record->delete();
                        })
                ])
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\ForceDeleteBulkAction::make(),
                    Tables\Actions\RestoreBulkAction::make(),
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
            'index' => Pages\ListStages::route('/'),
            'create' => Pages\CreateStage::route('/create'),
            'view' => Pages\ViewStage::route('/{record}'),
            'edit' => Pages\EditStage::route('/{record}/edit'),
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
