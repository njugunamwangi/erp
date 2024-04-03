<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Spatie\Permission\Models\Role as ModelsRole;

class Role extends ModelsRole
{
    use HasFactory;

    public const ADMIN = 1;

    public const CUSTOMER = 2;

    public const STAFF = 3;
}
