<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, HasUuids, Notifiable, SoftDeletes;

    protected $fillable = [
        'name',
        'email',
        'phone_number',
        'password',
        'role',
        'avatar_url',
        'locale',
        'notify_badges',
        'provider',      // Added for Socialite
        'provider_id',   // Added for Socialite
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password'          => 'hashed',
            'notify_badges'     => 'boolean',
        ];
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function chatSessions(): HasMany
    {
        return $this->hasMany(ChatSession::class);
    }

    public function quizAttempts(): HasMany
    {
        return $this->hasMany(QuizAttempt::class);
    }

    public function progress(): HasMany
    {
        return $this->hasMany(UserProgress::class);
    }

    public function badges(): HasMany
    {
        return $this->hasMany(UserBadge::class);
    }

    public function streak(): HasOne
    {
        return $this->hasOne(UserStreak::class);
    }

    public function getMaskedEmailAttribute(): ?string
    {
        if (!$this->email) {
            return null;
        }

        $parts = explode('@', $this->email);
        if (count($parts) !== 2) {
            return null;
        }

        $username = $parts[0];
        $domain = $parts[1];

        // Ensure we reveal at most the first character for privacy
        $maskedUsername = substr($username, 0, 1) . str_repeat('•', 7);

        return $maskedUsername . '@' . $domain;
    }
}