<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable
{
    use HasFactory;

    protected $fillable = [
        'role_id',
        'direction_id',
        'name',
        'firstname',
        'email',
        'password',
        'phone',
        'photo',
        'status',
        'last_login',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'last_login' => 'datetime',
        ];
    }

    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }

    public function direction(): BelongsTo
    {
        return $this->belongsTo(Direction::class);
    }

    public function sessions(): HasMany
    {
        return $this->hasMany(UserSession::class);
    }

    public function reviewedAccountRequests(): HasMany
    {
        return $this->hasMany(AccountRequest::class, 'reviewed_by');
    }

    public function createdAccountRequests(): HasMany
    {
        return $this->hasMany(AccountRequest::class, 'created_user_id');
    }

    public function isAdmin(): bool
    {
        return $this->hasRole(Role::ADMIN);
    }

    public function isSg(): bool
    {
        return $this->hasRole(Role::SG);
    }

    public function isDirector(): bool
    {
        return $this->hasRole(Role::DIRECTEUR);
    }

    public function isActive(): bool
    {
        return strtoupper((string) $this->status) === 'ACTIVE';
    }

    public function isInactive(): bool
    {
        return ! $this->isActive();
    }

    public function hasRole(string $roleCode): bool
    {
        return strtoupper((string) ($this->role?->code ?? '')) === strtoupper($roleCode);
    }

    public function hasAnyRole(string ...$roleCodes): bool
    {
        foreach ($roleCodes as $roleCode) {
            if ($this->hasRole($roleCode)) {
                return true;
            }
        }

        return false;
    }

    public function inDirection(mixed $direction): bool
    {
        $directionId = $this->resolveDirectionId($direction);

        if ($directionId === null) {
            return false;
        }

        return (int) $this->direction_id === (int) $directionId;
    }

    public function canAccessDirection(mixed $direction): bool
    {
        if ($this->isAdmin() || $this->isSg()) {
            return true;
        }

        if (! $this->isDirector()) {
            return false;
        }

        return $this->inDirection($direction);
    }

    public function canViewDirection(mixed $direction): bool
    {
        return $this->canAccessDirection($direction);
    }

    public function canManageDirection(mixed $direction): bool
    {
        if ($this->isAdmin()) {
            return true;
        }

        if ($this->isSg()) {
            return false;
        }

        if (! $this->isDirector()) {
            return false;
        }

        return $this->inDirection($direction);
    }

    public function canEditDirection(mixed $direction): bool
    {
        return $this->canManageDirection($direction);
    }

    public function isInDirection(mixed $direction): bool
    {
        return $this->inDirection($direction);
    }

    public function belongsToDirection(mixed $direction): bool
    {
        return $this->inDirection($direction);
    }

    protected function resolveDirectionId(mixed $direction): ?int
    {
        if ($direction instanceof Direction) {
            return $direction->getKey();
        }

        if (is_numeric($direction)) {
            return (int) $direction;
        }

        if (is_array($direction) && array_key_exists('direction_id', $direction)) {
            return is_numeric($direction['direction_id']) ? (int) $direction['direction_id'] : null;
        }

        if (is_object($direction) && isset($direction->direction_id)) {
            return is_numeric($direction->direction_id) ? (int) $direction->direction_id : null;
        }

        if (is_string($direction) && trim($direction) !== '') {
            return ctype_digit(trim($direction)) ? (int) trim($direction) : null;
        }

        return null;
    }

    /*
    |--------------------------------------------------------------------------
    | Relations des notifications personnalisées
    |--------------------------------------------------------------------------
    */

    public function notifications(): HasMany
    {
        return $this->hasMany(UserNotification::class);
    }

    public function unreadNotifications(): HasMany
    {
        return $this->notifications()
            ->whereNull('read_at');
    }
}
