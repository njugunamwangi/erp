<?php

namespace App\Models;

use App\Enums\EntityType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

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
}
