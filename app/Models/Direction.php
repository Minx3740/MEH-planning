<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Direction extends Model
{
    protected $fillable = [
        'group_id',
        'name',
        'code',
        'description',
    ];

    public function group(): BelongsTo
    {
        return $this->belongsTo(Group::class);
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function activities(): HasMany
    {
        return $this->hasMany(Activity::class);
    }

    public function canBeAccessedBy(User $user): bool
    {
        return $user->canAccessDirection($this);
    }

    public function canBeManagedBy(User $user): bool
    {
        return $user->canManageDirection($this);
    }

    public function userCanView(User $user): bool
    {
        return $this->canBeAccessedBy($user);
    }

    public function userCanManage(User $user): bool
    {
        return $this->canBeManagedBy($user);
    }
}
