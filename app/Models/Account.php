<?php

namespace App\Models;

use App\Enums\AccountStatus;
use App\Enums\AccountType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Account extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $guarded = [];

    public function currency(): BelongsTo
    {
         return $this->belongsTo(Currency::class);
    }

    public function makeDefault() {
        $this->enabled = true;
        $this->save();
    }

    protected function casts(): array
    {
         return [
             'type' => AccountType::class,
             'status' => AccountStatus::class,
         ];
    }
}
