<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'device_id',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function subscriptions()
    {
        return $this->hasMany(Subscription::class);
    }

    public function activeSubscription()
    {
        return $this->hasOne(Subscription::class)
            ->where('status', 'active')
            ->where(function ($query) {
                $query->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            })
            ->latest();
    }

    public function transactions()
    {
        return $this->hasMany(Transaction::class);
    }

    public function downloadLogs()
    {
        return $this->hasMany(DownloadLog::class);
    }

    public function isPro(): bool
    {
        return $this->activeSubscription()->exists();
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    /**
     * Directly assign or upgrade subscription to user without license key friction.
     */
    public function grantSubscription(Plan|int $plan, ?int $customDays = null): Subscription
    {
        $planModel = $plan instanceof Plan ? $plan : Plan::findOrFail($plan);
        $duration = $customDays !== null ? $customDays : $planModel->duration_days;

        $startsAt = now();
        $expiresAt = $duration !== null ? $startsAt->copy()->addDays($duration) : null;

        // If user already has active subscription, extend from current expiry
        $currentActive = $this->activeSubscription()->first();
        if ($currentActive && $currentActive->expires_at && $currentActive->expires_at->isFuture()) {
            if ($expiresAt !== null) {
                $expiresAt = $currentActive->expires_at->copy()->addDays($duration);
            }
        }

        // Cancel previous active subscriptions to keep a single clean active state
        $this->subscriptions()->where('status', 'active')->update(['status' => 'cancelled']);

        return $this->subscriptions()->create([
            'plan_id' => $planModel->id,
            'starts_at' => $startsAt,
            'expires_at' => $expiresAt,
            'status' => 'active',
            'device_id' => $this->device_id,
        ]);
    }
}
