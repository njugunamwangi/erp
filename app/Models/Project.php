<?php

namespace App\Models;

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
}
