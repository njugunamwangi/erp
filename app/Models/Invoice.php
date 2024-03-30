<?php

namespace App\Models;

use App\InvoiceStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Invoice extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $guarded = [];

    protected function casts(): array {
        return [
            'items' => 'json',
            'status' => InvoiceStatus::class,
        ];
    }

    public function quote(): HasOne {
        return $this->hasOne(Quote::class);
    }

    public function user(): BelongsTo {
        return $this->belongsTo(User::class);
    }
}
