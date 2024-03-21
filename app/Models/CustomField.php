<?php

namespace App\Models;

use Filament\Forms\Components\TextInput;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class CustomField extends Model
{
    use HasFactory;

    protected $guarded = [];

    public function users(): BelongsToMany {
        return $this->belongsToMany(User::class);
    }

    public static function getForm(): array {
        return [
            TextInput::make('custom_field')
                ->required()
                ->maxLength(255),
        ];
    }
}
