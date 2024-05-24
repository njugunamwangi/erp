<?php

namespace App\Models;

use Filament\Forms\Components\Select;
use FilamentTiptapEditor\TiptapEditor;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Service extends Model
{
    use HasFactory;

    protected $guarded = [];

    public function equipment(): BelongsTo
    {
        return $this->belongsTo(Equipment::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public static function getForm(): array
    {
        return [
            Select::make('equipment_id')
                ->relationship('equipment', 'registration')
                ->searchable()
                ->preload()
                ->required(),
            Select::make('user_id')
                ->relationship('user', 'name')
                ->options(Role::find(Role::TECHNICIAN)->users()->get()->pluck('name', 'id'))
                ->required()
                ->label('Technician')
                ->searchable()
                ->preload(),
            TiptapEditor::make('description')
                ->required()
                ->extraInputAttributes(['style' => 'min-height: 12rem;'])
                ->columnSpanFull(),
        ];
    }
}
