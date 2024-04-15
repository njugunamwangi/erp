<?php

namespace App\Filament\Resources\TaskResource\Pages;

use App\Enums\Material;
use App\Filament\Resources\TaskResource;
use Filament\Actions;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Forms\Components\Actions\Action as ActionsAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
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
                    ->color('danger')
                    ->form([
                        Tabs::make('items')
                            ->tabs([
                                Tabs\Tab::make('Accommodation')
                                    ->schema([
                                        Repeater::make('accom')
                                            ->columnSpanFull()
                                            ->hiddenLabel()
                                            ->schema([
                                                DatePicker::make('date'),
                                                TextInput::make('amount')
                                                    ->numeric()
                                                    ->live()
                                                    ->required()
                                                    ->prefix('Kes')
                                                    ->formatStateUsing(function (TextInput $component, ?string $state) {
                                                        $component->state(number_format($state, 2, '.', ','));
                                                    })
                                            ])
                                            ->deleteAction(
                                                fn (ActionsAction $action) => $action->requiresConfirmation(),
                                            )
                                            ->itemLabel(fn (array $state): ?string => $state['date'] ?? null)
                                            ->columns(2)
                                            ->cloneable()
                                            ->addActionLabel('Add Accommodation'),
                                        Placeholder::make('accom_subtotal')
                                            ->label('Sub Total')
                                            ->live()
                                            ->content(function(Get $get) {
                                                $items = $get('accom');

                                                $subtotal = 0;

                                                foreach($items as $item) {
                                                    $subtotal += $item['amount'];
                                                }

                                                return 'Kes ' . number_format($subtotal, 2, '.', ',');
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
                                        Repeater::make('material')
                                            ->hiddenLabel()
                                            ->schema([
                                                Select::make('type')
                                                    ->enum(Material::class)
                                                    ->options(Material::class)
                                                    ->searchable(),
                                                TextInput::make('amount')
                                                    ->numeric()
                                                    ->live()
                                                    ->required()
                                                    ->prefix('Kes')
                                                    ->afterStateUpdated(function (TextInput $component, ?string $state) {
                                                        $component->state(number_format($state, 2, '.', ','));
                                                    })
                                            ])
                                            ->deleteAction(
                                                fn (ActionsAction $action) => $action->requiresConfirmation(),
                                            )
                                            ->columns(2)
                                            ->cloneable()
                                            ->addActionLabel('Add Material'),
                                        Placeholder::make('accom_subtotal')
                                            ->label('Sub Total')
                                            ->live()
                                            ->content(function(Get $get) {
                                                $items = $get('material');

                                                $subtotal = 0;

                                                foreach($items as $item) {
                                                    $subtotal += $item['amount'];
                                                }

                                                return 'Kes ' . number_format($subtotal, 2, '.', ',');
                                            })
                                    ]),
                                Tabs\Tab::make('Miscellaneous')
                                    ->schema([
                                        Repeater::make('misc')
                                            ->hiddenLabel()
                                            ->schema([
                                                TextInput::make('quantity')
                                                    ->numeric()
                                                    ->live()
                                                    ->required()
                                                    ->default(1),
                                                TextInput::make('description')
                                                    ->required()
                                                    ->placeholder('Airtime'),
                                                TextInput::make('unit_price')
                                                    ->required()
                                                    ->live()
                                                    ->numeric()
                                                    ->default(1000),
                                                Placeholder::make('sum')
                                                    ->label('Sub Total')
                                                    ->live()
                                                    ->content(function (Get $get) {
                                                        return 'Kes '.number_format($get('quantity') * $get('unit_price'), 2, '.', ',');
                                                    }),
                                            ])
                                            ->deleteAction(
                                                fn (ActionsAction $action) => $action->requiresConfirmation(),
                                            )
                                            ->columns(4)
                                            ->addActionLabel('Add Misc.'),
                                        Placeholder::make('misc_subtotal')
                                            ->label('Sub Total')
                                            ->live()
                                            ->content(function(Get $get) {
                                                $items = $get('misc');

                                                $subtotal = 0;

                                                foreach($items as $item) {
                                                    $subtotal += $item['quantity'] * $item['unit_price'];
                                                }

                                                return 'Kes ' . number_format($subtotal, 2, '.', ',');
                                            })
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
