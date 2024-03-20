<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Stage extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $guarded = [];

    public function users(): HasMany {
        return $this->hasMany(User::class);
    }

    public function pipelines(): HasMany {
        return $this->hasMany(Pipeline::class);
    }
}