<?php

namespace App\Models;

use App\Enums\EquipmentType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Equipment extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $guarded = [];

    protected function casts(): array
    {
         return [
             'type' => EquipmentType::class
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
}
