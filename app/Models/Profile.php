<?php

namespace App\Models;

use App\Enums\EntityType;
use Awcodes\Curator\Models\Media;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Profile extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected function casts(): array
    {
         return [
             'entity' => EntityType::class
         ];
    }

    public function media(): BelongsTo
    {
         return $this->belongsTo(Media::class);
    }
}
