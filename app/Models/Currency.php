<?php

namespace App\Models;

use Filament\Forms\Components\TextInput;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Wallo\FilamentSelectify\Components\ToggleButton;

class Currency extends Model
{
    use HasFactory;

    protected $guarded = [];

    public function quotes(): HasMany
    {
        return $this->hasMany(Quote::class);
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    public function accounts(): HasMany
    {
        return $this->hasMany(Account::class);
    }

    public static function getForm(): array
    {
        return [
            TextInput::make('name')
                ->required()
                ->maxLength(255),
            TextInput::make('abbr')
                ->required()
                ->maxLength(255),
            TextInput::make('code')
                ->required()
                ->maxLength(255),
            TextInput::make('subunit_name')
                ->required()
                ->maxLength(255),
            TextInput::make('locale')
                ->required()
                ->maxLength(255),
            TextInput::make('precision')
                ->required()
                ->numeric(),
            TextInput::make('subunit')
                ->required()
                ->numeric(),
            TextInput::make('symbol')
                ->required()
                ->maxLength(255),
            TextInput::make('decimal_mark')
                ->required()
                ->maxLength(255),
            TextInput::make('thousands_separator')
                ->required()
                ->maxLength(255),
            ToggleButton::make('symbol_first')
                ->onLabel('Yes')
                ->offLabel('No')
                ->onColor('info')
                ->offColor('danger')
                ->required(),
        ];
    }
}
