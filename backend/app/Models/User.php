<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable implements FilamentUser
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'calorie_target',
        'gender',
        'age',
        'height_cm',
        'weight_kg',
        'target_weight_kg',
        'activity_level',
        'goal',
    ];

    // is_admin and is_banned are intentionally NOT in $fillable.
    // Use forceFill() in trusted admin-only contexts (Filament pages).

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        // Internal moderation flags. Filament reads these as attributes, which
        // $hidden does not affect; this only keeps them out of API payloads.
        'is_admin',
        'is_banned',
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
            'is_admin' => 'boolean',
            'is_banned' => 'boolean',
        ];
    }

    /**
     * Determine if the user can access a Filament panel.
     */
    public function canAccessPanel(Panel $panel): bool
    {
        return $this->is_admin === true;
    }

    protected static function booted(): void
    {
        // Revoke all tokens the moment a user is suspended, so an active
        // session can't outlive the ban.
        static::updated(function (User $user): void {
            if ($user->wasChanged('is_banned') && $user->is_banned) {
                $user->tokens()->delete();
            }
        });
    }

    public function logs(): HasMany
    {
        return $this->hasMany(FoodLog::class);
    }

    public function dailySummaries(): HasMany
    {
        return $this->hasMany(DailySummary::class);
    }
}
