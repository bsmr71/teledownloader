<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DownloadLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'device_id',
        'ip_address',
        'download_date',
        'downloads_count',
    ];

    protected $casts = [
        'download_date' => 'date',
        'downloads_count' => 'integer',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
