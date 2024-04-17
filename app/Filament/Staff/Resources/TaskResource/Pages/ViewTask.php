<?php

namespace App\Filament\Staff\Resources\TaskResource\Pages;

use App\Filament\Staff\Resources\TaskResource;
use App\Models\Expense;
use App\Models\Task;
use Filament\Actions;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Filament\Support\Enums\MaxWidth;

class ViewTask extends ViewRecord
{
    protected static string $resource = TaskResource::class;

    protected function getHeaderActions(): array
    {
        return [
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
        ];
    }
}
