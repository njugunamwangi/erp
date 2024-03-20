<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Resources\UserResource;
use App\Models\Stage;
use App\Models\User;
use Filament\Actions;
use Filament\Resources\Components\Tab;
use Filament\Resources\Pages\ListRecords;

class ListUsers extends ListRecords
{
    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }

    public function getTabs(): array
    {
        $tabs = [];

        $tabs['all'] = Tab::make('All Users')
            ->badge(User::count());

        $stages = Stage::orderBy('position')->withCount('users')->get();

        foreach ($stages as $stage) {
            $tabs[str($stage->stage)->slug()->toString()] = Tab::make($stage->stage)
                ->badge($stage->users_count)
                ->modifyQueryUsing(function ($query) use ($stage) {
                    return $query->where('stage_id', $stage->id);
                });
        }

        $tabs['archived'] = Tab::make('Archived')
            ->badge(User::onlyTrashed()->count())
            ->modifyQueryUsing(function ($query) {
                return $query->onlyTrashed();
            });

        return $tabs;
    }
}
