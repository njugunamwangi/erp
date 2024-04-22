<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Feedback extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected function casts(): array
    {
         return [
            //
         ];
    }

    public function task(): BelongsTo
    {
         return $this->belongsTo(Task::class);
    }
}
