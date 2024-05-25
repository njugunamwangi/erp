<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;

class TaskCalendar extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-calendar-days';
    protected static string $view = 'filament.pages.task-calendar';
}
