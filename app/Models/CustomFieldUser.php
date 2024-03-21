<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\Pivot;

class CustomFieldUser extends Pivot
{
    use HasFactory;

    protected $table = 'custom_field_users';

    protected $guarded = [];

    public function user(): BelongsTo {
        return $this->belongsTo(User::class);
    }

    public function customField(): BelongsTo {
        return $this->belongsTo(CustomField::class);
    }
}
