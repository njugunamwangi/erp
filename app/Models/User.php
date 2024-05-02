<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Filament\Models\Contracts\FilamentUser;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Storage;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Laravel\Jetstream\HasProfilePhoto;
use Laravel\Jetstream\HasTeams;
use Laravel\Sanctum\HasApiTokens;
use SamuelMwangiW\Africastalking\Facades\Africastalking;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable implements FilamentUser
{
    use HasApiTokens;
    use HasFactory;
    use HasProfilePhoto;
    use HasRoles;
    use HasTeams;
    use Notifiable;
    use SoftDeletes;
    use TwoFactorAuthenticatable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'phone',
        'lead_id',
        'avatar_url',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'two_factor_recovery_codes',
        'two_factor_secret',
    ];

    /**
     * The accessors to append to the model's array form.
     *
     * @var array<int, string>
     */
    protected $appends = [
        'profile_photo_url',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function getFilamentAvatarUrl(): ?string
    {
        return $this->avatar_url ? Storage::url($this->avatar_url) : 'https://ui-avatars.com/api/?name=' . $this->name ;
    }

    public function canAccessPanel(\Filament\Panel $panel): bool
    {
        if ($panel->getId() == 'admin') {

            return $this->hasRole(Role::ADMIN);

        } else {

            return $this->hasRole([
                Role::STAFF,
            ]);

        }
    }

    public function quotes(): HasMany
    {
        return $this->hasMany(Quote::class);
    }

    public function pipelines(): HasMany
    {
        return $this->hasMany(Pipeline::class);
    }

    public function documents(): HasMany
    {
        return $this->hasMany(Document::class);
    }

    public function customFieldUsers(): HasMany
    {
        return $this->hasMany(CustomFieldUser::class);
    }

    public function staffCompletedTasks(): HasMany
    {
        return $this->hasMany(Task::class, 'assigned_to')->where('is_completed', true);
    }

    public function staffIncompleteTasks(): HasMany
    {
        return $this->hasMany(Task::class, 'assigned_to')->where('is_completed', false);
    }

    public function completedTasks(): HasMany
    {
        return $this->hasMany(Task::class, 'assigned_for')->where('is_completed', true);
    }

    public function incompleteTasks(): HasMany
    {
        return $this->hasMany(Task::class, 'assigned_for')->where('is_completed', false);
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class);
    }

    public function stage(): BelongsTo
    {
        return $this->belongsTo(Stage::class);
    }

    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class);
    }

    public function routeNotificationForAfricasTalking($notification)
    {
        return $this->phone;
    }

    public function sendSms($message)
    {

        $message = strip_tags($message);

        Africastalking::sms()
            ->message($message)
            ->to($this->phone)
            ->bulk()
            ->enqueue()
            ->send();
    }
}
