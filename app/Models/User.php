<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'phone',
        'password',
        'user_priviliages',
        'otp',
        'role',
        'otp_expires_at',
        'email_verification_token',
        'email_verification_sent_at',
        'email_verified_at', 
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'email_verification_token',
        'otp',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'email_verification_sent_at' => 'datetime',
        'otp_expires_at' => 'datetime',
        'password' => 'hashed',
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
            'email_verification_sent_at' => 'datetime',
            'otp_expires_at' => 'datetime',
            'password' => 'hashed',
        ];
    }
    public function organization()
{
    return $this->hasOne(\App\Models\Organization::class);
}
public function subscription()
{
    return $this->hasOne(\App\Models\Subscription::class);
}

public function isSubscribed()
{
    return $this->subscription && $this->subscription->isValid();
}
public function subscriptionRequests()
{
    return $this->hasMany(\App\Models\SubscriptionRequest::class);
}
public function activeSubscription()
{
    return $this->hasOne(Subscription::class)
        ->where('is_active', true)
        ->whereDate('ends_at', '>=', now())
        ->latest();
}

/**
 * Get all user's subscriptions
 */
public function subscriptions()
{
    return $this->hasMany(Subscription::class);
}

/**
 * Check if user has active subscription
 */
public function hasActiveSubscription(): bool
{
    return $this->activeSubscription()->exists();
}
}