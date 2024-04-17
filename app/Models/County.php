<?php

namespace App\Models;

use Filament\Forms\Components\TextInput;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class County extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $guarded = [];

    public static function getForm(): array
    {
        return [
            TextInput::make('county')
                ->required()
                ->maxLength(255),
            TextInput::make('county_code')
                ->required()
                ->unique(ignoreRecord: true)
                ->maxLength(255),
        ];
    }
}
