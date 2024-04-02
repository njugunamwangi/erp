<?php

namespace App\Models;

use App\QuoteSeries;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Quote extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'items' => 'json',
            'series' => QuoteSeries::class,
        ];
    }

    public function invoice(): HasOne {
        return $this->hasOne(Invoice::class);
    }

    public function user(): BelongsTo {
        return $this->belongsTo(User::class);
    }

    public function vertical(): BelongsTo {
        return $this->belongsTo(Vertical::class);
    }
}
