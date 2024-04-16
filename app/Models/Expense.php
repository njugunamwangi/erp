<?php

namespace App\Models;

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
}
