<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Resources\UserResource;
use App\Filament\Resources\UserResource\Widgets\StaffTasksWidget;
use App\Filament\Resources\UserResource\Widgets\TasksWidget;
use App\Models\Vertical;
use App\QuoteSeries;
use Filament\Actions;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Forms\Components\Fieldset;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Resources\Pages\ViewRecord;

class ViewUser extends ViewRecord
{
    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ActionGroup::make([
                Actions\EditAction::make(),
                Action::make('quote')
                    ->label('Generate Quote')
                    ->color('success')
                    ->icon('heroicon-o-document-check')
                    ->modalSubmitActionLabel('Generate Quote')
                    ->form([
                        Grid::make(2)
                            ->schema([
                                Select::make('vertical_id')
                                    ->label('Vertical')
                                    ->options(Vertical::all()->pluck('vertical', 'id'))
                                    ->searchable()
                                    ->preload()
                                    ->required(),
                                Select::make('series')
                                    ->required()
                                    ->enum(QuoteSeries::class)
                                    ->options(QuoteSeries::class)
                                    ->searchable()
                                    ->preload()
                                    ->default(QuoteSeries::IN2QUT->name),
                            ]),
                            Fieldset::make('Quote Summary')
                                ->schema([
                                    Section::make()
                                        ->schema([
                                            Repeater::make('items')
                                                ->columns(4)
                                                ->live()
                                                ->schema([
                                                    TextInput::make('quantity')
                                                        ->numeric()
                                                        ->required()
                                                        ->live()
                                                        ->default(1),
                                                    TextInput::make('description')
                                                        ->required()
                                                        ->placeholder('Aerial Spraying'),
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
                                                ->afterStateUpdated(function (Get $get, Set $set) {
                                                    self::updateTotals($get, $set);
                                                })
                                                ->addActionLabel('Add Item')
                                                ->columnSpanFull(),
                                        ]),
                                    Section::make()
                                        ->schema([
                                            TextInput::make('subtotal')
                                                ->numeric()
                                                ->readOnly()
                                                ->prefix('Kes')
                                                ->afterStateHydrated(function (Get $get, Set $set) {
                                                    self::updateTotals($get, $set);
                                                }),
                                            TextInput::make('taxes')
                                                ->suffix('%')
                                                ->required()
                                                ->numeric()
                                                ->default(16)
                                                ->live(true)
                                                ->afterStateUpdated(function (Get $get, Set $set) {
                                                    self::updateTotals($get, $set);
                                                }),
                                            TextInput::make('total')
                                                ->numeric()
                                                ->readOnly()
                                                ->prefix('Kes'),
                                        ]),
                                ])
                    ]),
                Action::make('invoice')
                    ->label('Generate Invoice')
                    ->color('primary')
                    ->icon('heroicon-o-clipboard-document-check')
            ])
        ];
    }

    public static function updateTotals(Get $get, Set $set): void
    {
        $items = collect($get('items'));

        $subtotal = 0;

        foreach ($items as $item) {
            $aggregate = $item['quantity'] * $item['unit_price'];

            $subtotal += $aggregate;
        }

        $set('subtotal', number_format($subtotal, 2, '.', ''));
        $set('total', number_format($subtotal + ($subtotal * ($get('taxes') / 100)), 2, '.', ''));
    }

    protected function getHeaderWidgets(): array {
        return [
            TasksWidget::class,
        ];
    }
}
