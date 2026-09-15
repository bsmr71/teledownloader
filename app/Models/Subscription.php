<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Subscription extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'plan_id',
        'license_key',
        'starts_at',
        'expires_at',
        'status',
        'device_id',
    ];

    protected $casts = [
        'starts_at' => 'datetime',
        'expires_at' => 'datetime',
    ];

    public static function boot()
    {
        parent::boot();

        static::creating(function ($subscription) {
            if (empty($subscription->license_key)) {
                $subscription->license_key = 'TLD-PRO-'.strtoupper(Str::random(4)).'-'.strtoupper(Str::random(4));
            }
        });
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function plan()
    {
        return $this->belongsTo(Plan::class);
    }

    public function isValid(): bool
    {
        if ($this->status !== 'active') {
            return false;
        }

        if (is_null($this->expires_at)) {
            return true;
        }

        return $this->expires_at->isFuture();
    }

    public function extendDays(int $days): self
    {
        $baseDate = ($this->expires_at && $this->expires_at->isFuture()) ? $this->expires_at : now();
        $this->expires_at = $baseDate->copy()->addDays($days);
        $this->status = 'active';
        $this->save();

        return $this;
    }

    public function makeLifetime(): self
    {
        $this->expires_at = null;
        $this->status = 'active';
        $this->save();

        return $this;
    }

    public function cancel(): self
    {
        $this->status = 'cancelled';
        $this->save();

        return $this;
    }
}
