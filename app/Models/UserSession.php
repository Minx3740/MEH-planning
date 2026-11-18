<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserSession extends Model
{
    protected $fillable = [
        'user_id',
        'session_key_hash',
        'ip_address',
        'device_type',
        'operating_system',
        'status',
        'connected_at',
        'last_activity_at',
        'revoked_at',
    ];

    protected $hidden = [
        'session_key_hash',
    ];

    protected function casts(): array
    {
        return [
            'connected_at' => 'datetime',
            'last_activity_at' => 'datetime',
            'revoked_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
