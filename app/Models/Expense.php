<?php

namespace App\Models;

use App\Enums\Material;
use Filament\Forms\Components\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Tabs;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Get;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Expense extends Model
{
    use HasFactory;

    protected $guarded = [];

    public function casts(): array {
        return [
            'accommodation' => 'json',
            'subsistence' => 'json',
            'fuel' => 'json',
            'labor' => 'json',
            'material' => 'json',
            'misc' => 'json',
        ];
    }

    public function task(): BelongsTo
    {
         return $this->belongsTo(Task::class);
    }

    public static function getForm(): array
    {
         return [
            Tabs::make('items')
                ->tabs([
                    Tabs\Tab::make('Accommodation')
                        ->icon('heroicon-o-home-modern')
                        ->schema([
                            Repeater::make('accommodation')
                                ->columnSpanFull()
                                ->hiddenLabel()
                                ->schema([
                                    DatePicker::make('date')
                                        ->required(),
                                    TextInput::make('amount')
                                        ->numeric()
                                        ->live()
                                        ->required()
                                        ->prefix('Kes')
                                ])
                                ->deleteAction(
                                    fn (Action $action) => $action->requiresConfirmation(),
                                )
                                ->itemLabel(fn (array $state): ?string => $state['date'] ?? null)
                                ->columns(2)
                                ->cloneable()
                                ->addActionLabel('Add Accommodation'),
                            Placeholder::make('accommodation_subtotal')
                                ->label('Sub Total')
                                ->live()
                                ->content(function(Get $get) {
                                    $items = $get('accommodation');

                                    $subtotal = 0;

                                    foreach($items as $item) {
                                        $subtotal += $item['amount'];
                                    }

                                    return 'Kes ' . number_format($subtotal, 2, '.', ',');
                                })
                        ]),
                    Tabs\Tab::make('Food & Beverage')
                        ->icon('heroicon-o-adjustments-vertical')
                        ->schema([
                            Repeater::make('subsistence')
                                ->columnSpanFull()
                                ->hiddenLabel()
                                ->schema([
                                    DateTimePicker::make('date')
                                        ->seconds(false)
                                        ->required(),
                                    TextInput::make('amount')
                                        ->numeric()
                                        ->live()
                                        ->required()
                                        ->prefix('Kes')
                                ])
                                ->deleteAction(
                                    fn (Action $action) => $action->requiresConfirmation(),
                                )
                                ->itemLabel(fn (array $state): ?string => $state['date'] ?? null)
                                ->columns(2)
                                ->cloneable()
                                ->addActionLabel('Add Subsistence'),
                            Placeholder::make('subsistence_subtotal')
                                ->label('Sub Total')
                                ->live()
                                ->content(function(Get $get) {
                                    $items = $get('subsistence');

                                    $subtotal = 0;

                                    foreach($items as $item) {
                                        $subtotal += $item['amount'];
                                    }

                                    return 'Kes ' . number_format($subtotal, 2, '.', ',');
                                })
                        ]),
                    Tabs\Tab::make('Fuel & Logistics')
                        ->icon('heroicon-o-truck')
                        ->schema([
                            Repeater::make('fuel')
                                ->columnSpanFull()
                                ->hiddenLabel()
                                ->schema([
                                    DateTimePicker::make('date')
                                        ->seconds(false)
                                        ->required(),
                                    TextInput::make('amount')
                                        ->numeric()
                                        ->live()
                                        ->required()
                                        ->prefix('Kes')
                                ])
                                ->deleteAction(
                                    fn (Action $action) => $action->requiresConfirmation(),
                                )
                                ->itemLabel(fn (array $state): ?string => $state['date'] ?? null)
                                ->columns(2)
                                ->cloneable()
                                ->addActionLabel('Add'),
                            Placeholder::make('fuel_subtotal')
                                ->label('Sub Total')
                                ->live()
                                ->content(function(Get $get) {
                                    $items = $get('fuel');

                                    $subtotal = 0;

                                    foreach($items as $item) {
                                        $subtotal += $item['amount'];
                                    }

                                    return 'Kes ' . number_format($subtotal, 2, '.', ',');
                                })
                        ]),
                    Tabs\Tab::make('Labor')
                        ->icon('heroicon-o-briefcase')
                        ->schema([
                            Repeater::make('labor')
                                ->columnSpanFull()
                                ->hiddenLabel()
                                ->schema([
                                    DatePicker::make('date')
                                        ->required(),
                                    TextInput::make('amount')
                                        ->numeric()
                                        ->live()
                                        ->required()
                                        ->prefix('Kes')
                                ])
                                ->deleteAction(
                                    fn (Action $action) => $action->requiresConfirmation(),
                                )
                                ->itemLabel(fn (array $state): ?string => $state['date'] ?? null)
                                ->columns(2)
                                ->cloneable()
                                ->addActionLabel('Add'),
                            Placeholder::make('labor_subtotal')
                                ->label('Sub Total')
                                ->live()
                                ->content(function(Get $get) {
                                    $items = $get('labor');

                                    $subtotal = 0;

                                    foreach($items as $item) {
                                        $subtotal += $item['amount'];
                                    }

                                    return 'Kes ' . number_format($subtotal, 2, '.', ',');
                            })
                        ]),
                    Tabs\Tab::make('Material')
                        ->icon('heroicon-o-beaker')
                        ->schema([
                            Repeater::make('material')
                                ->hiddenLabel()
                                ->schema([
                                    Select::make('type')
                                        ->enum(Material::class)
                                        ->options(Material::class)
                                        ->searchable()
                                        ->required(),
                                    TextInput::make('amount')
                                        ->numeric()
                                        ->live()
                                        ->required()
                                        ->prefix('Kes')
                                ])
                                ->deleteAction(
                                    fn (Action $action) => $action->requiresConfirmation(),
                                )
                                ->columns(2)
                                ->cloneable()
                                ->addActionLabel('Add Material'),
                            Placeholder::make('material_subtotal')
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
                        ->icon('heroicon-o-bookmark-square')
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
                                        ->default(0),
                                    Placeholder::make('sum')
                                        ->label('Sub Total')
                                        ->live()
                                        ->content(function (Get $get) {
                                            return 'Kes '.number_format($get('quantity') * $get('unit_price'), 2, '.', ',');
                                        }),
                                ])
                                ->deleteAction(
                                    fn (Action $action) => $action->requiresConfirmation(),
                                )
                                ->cloneable()
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
                    ]),
            Placeholder::make('total')
                ->label('Total Expenses')
                ->live()
                ->content(function(Get $get) {
                    // Accommodation
                    $accom_items = collect($get('accommodation'));
                    $accom_sub = 0;

                    foreach($accom_items as $item) {
                        $accom_sub += $item['amount'];
                    }

                    // Subsistence
                    $subsistence = collect($get('subsistence'));
                    $subsistence_sub = 0;

                    foreach($subsistence as $item) {
                        $subsistence_sub += $item['amount'];
                    }

                    // Fuel & Logistics
                    $fuel = collect($get('fuel'));
                    $fuel_sub = 0;

                    foreach($fuel as $item) {
                        $fuel_sub += $item['amount'];
                    }

                    // Labor
                    $labor = collect($get('labor'));
                    $labor_sub = 0;

                    foreach($labor as $item) {
                        $labor_sub += $item['amount'];
                    }

                    // Material
                    $material = collect($get('material'));
                    $material_sub = 0;

                    foreach($material as $item) {
                        $material_sub += $item['amount'];
                    }

                    // Miscellaneous
                    $misc = collect($get('misc'));
                    $misc_sub = 0;

                    foreach($misc as $item) {
                        $aggregate = $item['quantity'] * $item['unit_price'];

                        $misc_sub += $aggregate;
                    }

                    $total = $accom_sub + $subsistence_sub + $fuel_sub + $labor_sub + $material_sub + $misc_sub;

                    return 'Kes ' . number_format($total, 2, '.', ',');
                }),
        ];
    }
}
