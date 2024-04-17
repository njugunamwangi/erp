<?php

namespace App\Filament\Resources\TaskResource\Pages;

use App\Enums\Material;
use App\Filament\Resources\TaskResource;
use App\Models\Expense;
use App\Models\Task;
use Filament\Actions;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Forms\Components\Actions\Action as ActionsAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Tabs;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Filament\Support\Enums\MaxWidth;

class ViewTask extends ViewRecord
{
    protected static string $resource = TaskResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ActionGroup::make([
                Actions\EditAction::make()
                    ->icon('heroicon-o-pencil-square'),
                Action::make('expenses')
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
                    ->action(function(array $data) {
                        $task = $this->getRecord();

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
        ];
    }
}
