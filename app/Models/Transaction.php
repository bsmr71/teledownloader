<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Transaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'plan_id',
        'order_id',
        'gross_amount',
        'payment_gateway',
        'payment_type',
        'payment_status',
        'payment_url',
        'qr_string',
        'va_number',
        'payload',
        'paid_at',
    ];

    protected $casts = [
        'gross_amount' => 'integer',
        'payload' => 'array',
        'paid_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function plan()
    {
        return $this->belongsTo(Plan::class);
    }

    public function isPaid(): bool
    {
        return $this->payment_status === 'paid';
    }
}
