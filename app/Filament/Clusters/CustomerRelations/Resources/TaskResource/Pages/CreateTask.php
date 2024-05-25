<?php

namespace App\Filament\Clusters\CustomerRelations\Resources\TaskResource\Pages;

use App\Filament\Clusters\CustomerRelations\Resources\TaskResource;
use App\Filament\Staff\Resources\TaskResource as ResourcesTaskResource;
use Filament\Notifications\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;

class CreateTask extends CreateRecord
{
    protected static string $resource = TaskResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['assigned_by'] = auth()->id();

        return $data;
    }

    protected function afterCreate(): void
    {
        $task = $this->getRecord();

        Notification::make()
            ->warning()
            ->icon('heroicon-o-bolt')
            ->title('Task assigned')
            ->body('Attend to task #'.$task->id.' for '.$task->assignedFor->name)
            ->actions([
                Action::make('view')
                    ->markAsRead()
                    ->url(ResourcesTaskResource::getUrl('view', ['record' => $task->id], panel: 'staff'))
                    ->color('warning'),
            ])
            ->sendToDatabase($task->assignedTo);
    }
}
