<?php

namespace App\Filament\Resources\TaskResource\Pages;

use App\Filament\Resources\TaskResource;
use App\Models\Role;
use App\Models\Task;
use Filament\Actions;
use Filament\Resources\Components\Tab;
use Filament\Resources\Pages\ListRecords;

class ListTasks extends ListRecords
{
    protected static string $resource = TaskResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }

    public function getTabs(): array
    {
        $tabs = [];

        if (auth()->user()->hasRole(Role::ADMIN)) {
            $tabs[] = Tab::make('All Tasks')
                ->badge(Task::count());
        }

        if (auth()->user()->hasRole(Role::STAFF)) {
            $tabs[] = Tab::make('My Tasks')
                ->badge(Task::where('assigned_to', auth()->id())->count())
                ->modifyQueryUsing(function ($query) {
                    return $query->where('assigned_to', auth()->id());
                });
        }

        if (auth()->user()->hasRole(Role::ADMIN)) {
            $tabs[] = Tab::make('Completed Tasks')
                ->badge(Task::where('is_completed', true)->count())
                ->modifyQueryUsing(function ($query) {
                    return $query->where('is_completed', true);
                });
        } elseif (auth()->user()->hasRole(Role::STAFF)) {
            $tabs[] = Tab::make('Completed Tasks')
                ->badge(Task::where('is_completed', true)->where('assigned_to', auth()->id())->count())
                ->modifyQueryUsing(function ($query) {
                    return $query->where('is_completed', true)->where('assigned_to', auth()->id());
                });
        }

        if (auth()->user()->hasRole(Role::ADMIN)) {
            $tabs[] = Tab::make('Incomplete Tasks')
                ->badge(Task::where('is_completed', false)->count())
                ->modifyQueryUsing(function ($query) {
                    return $query->where('is_completed', false);
                });
        } elseif (auth()->user()->hasRole(Role::STAFF)) {
            $tabs[] = Tab::make('Incomplete Tasks')
                ->badge(Task::where('is_completed', false)->where('assigned_to', auth()->id())->count())
                ->modifyQueryUsing(function ($query) {
                    return $query->where('is_completed', false)->where('assigned_to', auth()->id());
                });
        }

        return $tabs;
    }
}
