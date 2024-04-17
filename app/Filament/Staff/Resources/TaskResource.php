<?php

namespace App\Filament\Staff\Resources;

use App\Filament\Staff\Resources\TaskResource\Pages;
use App\Filament\Staff\Resources\TaskResource\RelationManagers;
use App\Models\Expense;
use App\Models\Role;
use App\Models\Task;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Infolists\Components\Actions\Action;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;
use Filament\Notifications\Actions\Action as ActionsAction;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Support\Enums\MaxWidth;
use Filament\Tables;
use Filament\Tables\Actions\Action as TablesActionsAction;
use Filament\Tables\Actions\BulkAction;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class TaskResource extends Resource
{
    protected static ?string $model = Task::class;

    protected static ?string $navigationGroup = 'Customer Relations';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('assigned_by')
                    ->required()
                    ->numeric(),
                Forms\Components\TextInput::make('assigned_to')
                    ->required()
                    ->numeric(),
                Forms\Components\TextInput::make('assigned_for')
                    ->required()
                    ->numeric(),
                Forms\Components\Textarea::make('description')
                    ->required()
                    ->columnSpanFull(),
                Forms\Components\DatePicker::make('due_date'),
                Forms\Components\Toggle::make('is_completed')
                    ->required(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('assignedBy.name')
                    ->label('Assigned By')
                    ->sortable(),
                Tables\Columns\TextColumn::make('assignedFor.name')
                    ->label('Customer')
                    ->sortable(),
                Tables\Columns\TextColumn::make('due_date')
                    ->date()
                    ->sortable(),
                Tables\Columns\IconColumn::make('is_completed')
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
                Tables\Actions\ViewAction::make(),
                TablesActionsAction::make('completed')
                    ->requiresConfirmation()
                    ->visible(fn($record) => $record->is_completed === false)
                    ->icon('heroicon-o-check-badge')
                    ->action(function($record) {
                        $record->is_completed = true;
                        $record->save();
                    })
                    ->after(function($record) {
                        $recipients = User::role(Role::ADMIN)->get();

                        foreach ($recipients as $recipient) {
                            Notification::make()
                                ->title('Task completed')
                                ->body(auth()->user()->name.' marked task #'.$record->id . ' as completed')
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
                        ->action(function(Task $task, array $data) {
                            if($task->expense) {
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
                                    'task_id' => $task->id,
                                    'accommodation' => $data['accommodation'],
                                    'subsistence' => $data['subsistence'],
                                    'fuel' => $data['fuel'],
                                    'labor' => $data['labor'],
                                    'material' => $data['material'],
                                    'misc' => $data['misc'],
                                ]);
                            }
                        })
                        ->after(function(Task $record) {
                            if($record->expense) {
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
                        })
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    BulkAction::make('completed')
                        ->requiresConfirmation()
                        ->color('success')
                        ->icon('heroicon-o-check-badge')
                        ->action(fn (Collection $records) => $records->each->completed())
                ]),
            ]);
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Section::make('Task Overview')
                    ->headerActions([
                        Action::make('completed')
                            ->label('Mark as completed')
                            ->requiresConfirmation()
                            ->visible(fn($record) => $record->is_completed === false)
                            ->action(function($record) {
                                $record->is_completed = true;
                                $record->save();
                            })
                            ->after(function($record) {
                                $recipients = User::role(Role::ADMIN)->get();

                                foreach ($recipients as $recipient) {
                                    Notification::make()
                                        ->title('Task completed')
                                        ->body(auth()->user()->name.' marked task #'.$record->id . ' as completed')
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
                    ])
                    ->schema([
                        TextEntry::make('assignedBy.name')
                            ->label('Assigned By'),
                        TextEntry::make('assignedFor.name')
                            ->label('Customer'),
                        TextEntry::make('due_date')
                            ->date(),
                        TextEntry::make('description')
                            ->html()
                            ->columnSpanFull()
                    ])->columns(3)
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
            'view' => Pages\ViewTask::route('/{record}'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->where('assigned_to', '=', auth()->id())
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }
}
