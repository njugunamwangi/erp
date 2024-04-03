<?php

namespace App\Filament\Widgets;

use App\Models\Project;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class ProjectStatsWidget extends BaseWidget
{
    protected function getColumns(): int
    {
        return 2;
    }

    protected function getStats(): array
    {
        return [
            Stat::make('Projects by Numbers', Project::all()->count())
                ->color('success')
                ->description('Number of projects we\'ve taken')
                ->descriptionIcon('heroicon-o-calculator'),
            Stat::make('Projects by Acreage', number_format(Project::all()->sum('acreage')).' acres')
                ->color('success')
                ->description('Number of acres we\'ve covered')
                ->descriptionIcon('heroicon-o-globe-alt'),
        ];
    }
}
