<?php

namespace App\Filament\Staff\Resources\TaskResource\Pages;

use App\Filament\Staff\Resources\TaskResource;
use App\Models\Task;
use Filament\Resources\Components\Tab;
use Filament\Resources\Pages\ListRecords;

class ListTasks extends ListRecords
{
    protected static string $resource = TaskResource::class;

    protected function getHeaderActions(): array
    {
        return [

        ];
    }

    public function getTabs(): array
    {
        $tabs = [];

        $tabs[] = Tab::make('All Tasks')
            ->badge(Task::where('assigned_to', '=', auth()->user()->id)
                ->count());

        $tabs[] = Tab::make('Completed Tasks')
            ->badge(Task::where('assigned_to', '=', auth()->user()->id)
                ->where('is_completed', true)
                ->count())
            ->modifyQueryUsing(function ($query) {
                return $query->where('is_completed', true);
            });

        $tabs[] = Tab::make('Incomplete Tasks')
            ->badge(Task::where('assigned_to', '=', auth()->user()->id)
                ->where('is_completed', false)
                ->count())
            ->modifyQueryUsing(function ($query) {
                return $query->where('is_completed', false);
            });

        return $tabs;
    }
}
