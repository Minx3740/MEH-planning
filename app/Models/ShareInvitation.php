<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ShareInvitation extends Model
{
    protected $fillable = [
        'share_id',
        'invited_by',
        'email',
        'status',
        'invitation_token_hash',
        'expires_at',
        'accepted_at',
    ];

    protected $hidden = [
        'invitation_token_hash',
    ];

    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
            'accepted_at' => 'datetime',
        ];
    }

    public function share(): BelongsTo
    {
        return $this->belongsTo(ActivityShare::class, 'share_id');
    }

    public function inviter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'invited_by');
    }

    public function notifications(): HasMany
    {
        return $this->hasMany(UserNotification::class, 'invitation_id');
    }
}
