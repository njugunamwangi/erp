<?php

namespace App\Filament\Resources\TaskResource\Pages;

use App\Filament\Resources\TaskResource;
use Filament\Actions;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Tabs;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Resources\Pages\ViewRecord;

class ViewTask extends ViewRecord
{
    protected static string $resource = TaskResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ActionGroup::make([
                Actions\EditAction::make()
                    ->icon('heroicon-o-pencil-square'),
                Action::make('expenses')
                    ->icon('heroicon-o-arrow-trending-up')
                    ->form([
                        Tabs::make('Tabs')
                            ->tabs([
                                Tabs\Tab::make('Accommodation')
                                    ->schema([
                                        Repeater::make('items')
                                            ->columnSpanFull()
                                            ->hiddenLabel()
                                            ->schema([
                                                TextInput::make('amount')
                                                    ->numeric()
                                                    ->live()
                                                    ->required()
                                                    ->dehydrateStateUsing(fn (string $state): string => number_format($state, 2, '.', ','))
                                            ])
                                            ->cloneable()
                                            ->addActionLabel('Add Accommodation'),
                                        Placeholder::make('subtotal')
                                            ->label('Sub Total')
                                            ->live()
                                            ->content(function(Get $get, Set $set) {
                                                $items = $get('items');
                                                return count($items);
                                            })
                                    ]),
                                Tabs\Tab::make('Food & Drinks')
                                    ->schema([
                                        // ...
                                    ]),
                                Tabs\Tab::make('Fuel & Logistics')
                                    ->schema([
                                        // ...
                                    ]),
                                Tabs\Tab::make('Labor')
                                    ->schema([
                                        // ...
                                    ]),
                                Tabs\Tab::make('Material')
                                    ->schema([
                                        // ...
                                    ]),
                                Tabs\Tab::make('Miscellaneous')
                                    ->schema([
                                        // ...
                                    ]),
                            ])
                    ])
                    ->action(function(array $data) {
                        $task = $this->getRecord();
                    })
                    ->after(function() {

                    })
            ])
        ];
    }
}
