<?php

namespace App\Livewire;

use App\Filament\Resources\TaskResource;
use App\Models\Role;
use App\Models\Task;
use Saade\FilamentFullCalendar\Data\EventData;
use Saade\FilamentFullCalendar\Widgets\FullCalendarWidget;

class TaskCalendar extends FullCalendarWidget
{
    public function fetchEvents(array $fetchInfo): array
    {
        return Task::query()
            ->where('due_date', '>=', $fetchInfo['start'])
            ->where('due_date', '<=', $fetchInfo['end'])
            ->when(! auth()->user()->hasRole(Role::ADMIN), function ($query) {
                return $query->where('assigned_to', auth()->id());
            })
            ->get()
            ->map(
                fn (Task $task) => EventData::make()
                    ->id($task->id)
                    ->title(strip_tags($task->description))
                    ->start($task->due_date)
                    ->end($task->due_date)
                    ->url(TaskResource::getUrl('edit', [$task->id]))
                    ->toArray()
            )
            ->toArray();
    }
}
