<?php

namespace App\Filament\Resources\ProjectResource\Pages;

use App\Filament\Resources\ProjectResource;
use App\Models\County;
use App\Models\Project;
use App\Models\Vertical;
use Filament\Actions;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;

class ListProjects extends ListRecords
{
    protected static string $resource = ProjectResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->icon('heroicon-o-squares-plus'),
            Action::make('backDate')
                ->icon('heroicon-o-calendar-days')
                ->color('success')
                ->form([
                    Select::make('user_id')
                        ->relationship('user', 'name')
                        ->searchable()
                        ->preload()
                        ->required(),
                    Select::make('vertical_id')
                        ->relationship('vertical', 'vertical')
                        ->searchable()
                        ->createOptionForm(Vertical::getForm())
                        ->editOptionForm(Vertical::getForm())
                        ->preload()
                        ->required(),
                    Select::make('county_id')
                        ->relationship('county', 'county')
                        ->searchable()
                        ->createOptionForm(County::getForm())
                        ->editOptionForm(County::getForm())
                        ->preload()
                        ->required(),
                    TextInput::make('acreage')
                        ->required()
                        ->numeric(),
                    DatePicker::make('created_at')
                ])
                ->action(function(array $data) {
                    Project::create([
                        'user_id' => $data['user_id'],
                        'vertical_id' => $data['vertical_id'],
                        'county_id' => $data['county_id'],
                        'acreage' => $data['acreage'],
                        'created_at' => $data['created_at'],
                    ]);

                    Notification::make()
                        ->title('Back date successful')
                        ->color('primary')
                        ->body('Project backdated successfully')
                        ->send();
                })
        ];
    }
}
