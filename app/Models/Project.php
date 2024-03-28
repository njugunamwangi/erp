<?php

namespace App\Models;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Project extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $guarded = [];

    public function user(): BelongsTo
    {
         return $this->belongsTo(User::class);
    }

    public function county(): BelongsTo
    {
         return $this->belongsTo(County::class);
    }

    public function vertical(): BelongsTo
    {
         return $this->belongsTo(Vertical::class);
    }

    public static function getForm(): array
    {
        return [
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
        ];
    }
}
