<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Task extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $guarded = [];

    protected $casts = [
        'due_date' => 'date',
        'is_completed' => 'boolean',
    ];

    public function quote(): HasOne
    {
        return $this->hasOne(Quote::class);
    }

    public function expense(): HasOne
    {
        return $this->hasOne(Expense::class);
    }

    public function feedback(): HasOne
    {
        return $this->hasOne(Feedback::class);
    }

    public function assignedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_by');
    }

    public function assignedTo(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function assignedFor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_for');
    }

    public function vertical(): BelongsTo
    {
        return $this->belongsTo(Vertical::class);
    }

    public function equipment(): BelongsToMany
    {
        return $this->belongsToMany(Equipment::class);
    }

    public function completed()
    {
        $this->is_completed = true;
        $this->save();
    }
}
