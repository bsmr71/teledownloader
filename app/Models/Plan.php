<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Plan extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'name',
        'price',
        'duration_days',
        'daily_download_limit',
        'features',
        'is_active',
        'is_featured',
    ];

    protected $casts = [
        'price' => 'integer',
        'duration_days' => 'integer',
        'daily_download_limit' => 'integer',
        'features' => 'array',
        'is_active' => 'boolean',
        'is_featured' => 'boolean',
    ];

    public function subscriptions()
    {
        return $this->hasMany(Subscription::class);
    }
}
