<?php

namespace App\Filament\Widgets;

use App\Models\Task;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class TasksStats extends BaseWidget
{
    protected static ?int $sort = 3;

    protected function getColumns(): int
    {
        return 2;
    }

    protected function getStats(): array
    {
        return [
            Stat::make('Completed Tasks', Task::query()->where('is_completed', true)->count())
                ->description('Total completed tasks')
                ->color('primary')
                ->descriptionIcon('heroicon-o-check-badge'),
            Stat::make('Incomplete Tasks', Task::query()->where('is_completed', false)->count())
                ->description('Total incomplete tasks')
                ->color('warning')
                ->descriptionIcon('heroicon-o-x-circle'),
        ];
    }
}
