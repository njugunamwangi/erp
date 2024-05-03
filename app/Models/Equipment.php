<?php

namespace App\Models;

use App\Enums\EquipmentType;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Equipment extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'type' => EquipmentType::class,
        ];
    }

    public function tasks(): BelongsToMany
    {
        return $this->belongsToMany(Task::class);
    }

    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class);
    }

    public function vertical(): BelongsTo
    {
        return $this->belongsTo(Vertical::class);
    }

    public function services(): HasMany
    {
        return $this->hasMany(Service::class);
    }

    public static function getForm(): array
    {
        return [
            TextInput::make('registration')
                ->required()
                ->maxLength(255),
            Select::make('vertical_id')
                ->relationship('vertical', 'vertical')
                ->required()
                ->searchable()
                ->preload(),
            Select::make('type')
                ->enum(EquipmentType::class)
                ->options(EquipmentType::class)
                ->required()
                ->searchable(),
            Select::make('brand_id')
                ->relationship('brand', 'brand')
                ->required()
                ->searchable()
                ->preload(),
        ];
    }
}
